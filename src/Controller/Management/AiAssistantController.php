<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Controller\Management;

use c975L\ConfigBundle\Repository\ConfigRepository;
use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\UiBundle\Contract\AiAssistantClientInterface;
use c975L\UiBundle\Service\AiRephraseClient;
use c975L\UiBundle\Service\AiUsageTracker;
use c975L\UiBundle\Service\ConfigEditUrlResolver;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Two independent AJAX endpoints, each gated by its own config and its own role (see Readme "AI
// Assistant"): ask() forwards to AiAssistantClientInterface (dashboard help, a shared/mutualized
// backend - kept to ROLE_SUPER_ADMIN), rephrase() forwards to AiRephraseClient (the site's own
// key/budget - "site-role-admin" is enough, nothing persisted). Neither shares state with the other -
// a site can enable one without the other.
class AiAssistantController extends AbstractController
{
    // EasyAdmin prefixes these with the Dashboard's own route name
    public const ASK_ROUTE = 'management_ui_ai_assistant_ask';
    public const REPHRASE_ROUTE = 'management_ui_ai_assistant_rephrase';

    // Every config slug the setup guide below links to individually
    private const LINKED_SLUGS = [
        'ui-ai-assistant-dashboard-enabled',
        'ui-ai-assistant-dashboard-endpoint',
        'ui-ai-assistant-dashboard-token',
        'ui-ai-assistant-rephrase-provider',
        'ui-ai-assistant-rephrase-api-key',
        'ui-ai-assistant-rephrase-base-uri',
        'ui-ai-assistant-rephrase-model',
    ];

    public function __construct(
        private readonly AiAssistantClientInterface $aiAssistantClient,
        private readonly AiRephraseClient $aiRephraseClient,
        private readonly AiUsageTracker $aiUsageTracker,
        private readonly ConfigServiceInterface $configService,
        private readonly ConfigRepository $configRepository,
        private readonly ConfigEditUrlResolver $configEditUrlResolver,
    ) {
    }

    // Custom admin page (not tied to any entity), linked from MenuProvider::getLinks() and from every
    // AiAlertProvider alert - shown even when nothing is configured yet, so the page itself is the
    // "what do I do" landing spot rather than dropping an editor straight into the raw Config list.
    // Gated at "site-role-admin" (the lower of the two bars below) - the template itself hides the
    // dashboard section entirely for a viewer without ROLE_SUPER_ADMIN
    #[AdminRoute(path: '/ui/ai-assistant', name: 'ui_ai_assistant_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted($this->configService->get('site-role-admin'));

        return $this->render('@c975LUi/management/ai_assistant.html.twig', [
            'assistantName' => 'Donovan',
            'enabled' => $this->aiAssistantClient->isEnabled(),
            'rephraseEnabled' => $this->aiRephraseClient->isEnabled(),
            'rephraseUsage' => $this->aiUsageTracker->getCurrentMonth(),
            'configLinks' => $this->configLinks(),
            'missingSlugs' => $this->missingSlugs(),
        ]);
    }

    // {slug: edit url}, one entry per LINKED_SLUGS - batched in one query rather than one per slug, then
    // resolved through ConfigEditUrlResolver (shared with Twig\ConfigLinkExtension's own single-slug case)
    private function configLinks(): array
    {
        $configsBySlug = [];
        foreach ($this->configRepository->findBy(['slug' => self::LINKED_SLUGS]) as $config) {
            $configsBySlug[$config->getSlug()] = $config;
        }

        $links = [];
        foreach (self::LINKED_SLUGS as $slug) {
            $links[$slug] = $this->configEditUrlResolver->resolve($configsBySlug[$slug] ?? null);
        }

        return $links;
    }

    // Slugs still needing attention - lets the template hide a setup step the moment its own value is
    // filled in, instead of showing all-or-nothing for a whole feature (e.g. dashboard-enabled and
    // -endpoint already set, only -token missing, shouldn't re-prompt for the first two).
    // "-base-uri" and "-model" are only blocking for euria (see AiRephraseClient::isEnabled()) -
    // anthropic/openai each call a fixed, hardcoded URI and fall back to a sensible default model instead
    private function missingSlugs(): array
    {
        $blockingSlugs = self::LINKED_SLUGS;
        if ('euria' !== $this->configService->get('ui-ai-assistant-rephrase-provider')) {
            $blockingSlugs = array_diff($blockingSlugs, ['ui-ai-assistant-rephrase-base-uri', 'ui-ai-assistant-rephrase-model']);
        }

        return array_values(array_filter($blockingSlugs, function (string $slug): bool {
            $value = $this->configService->get($slug);

            return 'ui-ai-assistant-dashboard-enabled' === $slug ? true !== $value : !$value;
        }));
    }

    #[AdminRoute(
        path: '/ui/ai-assistant/ask',
        name: 'ui_ai_assistant_ask',
        options: ['methods' => ['POST']]
    )]
    public function ask(Request $request): JsonResponse
    {
        // Stricter than the page itself and than rephrase(): this calls the mutualized, Laurent-paid
        // backend (see AiAssistantClient/Readme) - kept to ROLE_SUPER_ADMIN to bound who can spend
        // against a shared resource, not per-site config since every site shares the same concern
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if (!$this->isCsrfTokenValid(self::ASK_ROUTE, $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['error' => 'invalid_csrf'], 419);
        }

        $question = trim((string) $request->request->get('question', ''));
        if ('' === $question) {
            return new JsonResponse(['error' => 'empty_question'], 400);
        }

        $result = $this->aiAssistantClient->ask($question);

        return null === $result
            ? new JsonResponse(['error' => 'unavailable'], 503)
            : new JsonResponse($result);
    }

    #[AdminRoute(
        path: '/ui/ai-assistant/rephrase',
        name: 'ui_ai_assistant_rephrase',
        options: ['methods' => ['POST']]
    )]
    public function rephrase(Request $request): JsonResponse
    {
        // Calls the site's own key/budget (see AiRephraseClient), not a shared resource - the lower
        // "site-role-admin" bar is enough, still above a plain editor
        $this->denyAccessUnlessGranted($this->configService->get('site-role-admin'));

        if (!$this->isCsrfTokenValid(self::REPHRASE_ROUTE, $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['error' => 'invalid_csrf'], 419);
        }

        $text = trim((string) $request->request->get('text', ''));
        if ('' === $text) {
            return new JsonResponse(['error' => 'empty_text'], 400);
        }

        // AiRephraseClient::rephrase() falls back to "neutral"/"same" for any key outside its closed
        // STYLES/LENGTHS lists, so an unexpected/tampered value here is harmless - no need to validate it
        // twice
        $style = (string) $request->request->get('style', 'neutral');
        $length = (string) $request->request->get('length', 'same');

        $result = $this->aiRephraseClient->rephrase($text, $style, $length);

        return null === $result
            ? new JsonResponse(['error' => 'unavailable'], 503)
            : new JsonResponse(['text' => $result]);
    }
}
