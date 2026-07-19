<?= "<?php\n" ?>
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace <?= $namespace ?>;

use <?= $llm_client_full_name ?>;
use c975L\ConfigBundle\Controller\Management\ConfigCrudController;
use c975L\ConfigBundle\Repository\ConfigRepository;
use c975L\ConfigBundle\Service\ConfigServiceInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// Feeds the "extra_backends" block of UiBundle's ai_assistant.html.twig (overridden alongside this file
// at templates/bundles/c975LUiBundle/management/ai_assistant.html.twig) with this backend's status -
// entirely app-specific, UiBundle has no idea this backend exists
class <?= $class_name ?> extends AbstractExtension
{
    // Every config slug the setup guide links to individually
    private const LINKED_SLUGS = [
        'donovan-qa-llm-enabled',
        'donovan-qa-llm-provider',
        'donovan-qa-llm-api-key',
        'donovan-qa-llm-model',
        'donovan-qa-llm-base-uri',
        'donovan-qa-authorized-tokens',
    ];

    public function __construct(
        private readonly <?= $llm_client_short_name ?> $llmClient,
        private readonly ConfigServiceInterface $configService,
        private readonly ConfigRepository $configRepository,
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('donovan_qa_enabled', [$this->llmClient, 'isEnabled']),
            new TwigFunction('donovan_qa_missing_slugs', [$this, 'missingSlugs']),
            new TwigFunction('donovan_qa_config_links', [$this, 'configLinks']),
            new TwigFunction('donovan_qa_authorized_tokens', [$this, 'authorizedTokens']),
        ];
    }

    // Slugs still needing attention - "-model"/"-base-uri" only block for euria, mirroring
    // <?= $llm_client_short_name ?>::isEnabled() exactly so this guide never disagrees with what actually
    // gates the feature
    public function missingSlugs(): array
    {
        $blockingSlugs = self::LINKED_SLUGS;
        if ('euria' !== $this->configService->get('donovan-qa-llm-provider')) {
            $blockingSlugs = array_diff($blockingSlugs, ['donovan-qa-llm-base-uri', 'donovan-qa-llm-model']);
        }

        return array_values(array_filter(
            $blockingSlugs,
            fn (string $slug): bool => empty($this->configService->get($slug)),
        ));
    }

    // {slug: edit url}, one entry per LINKED_SLUGS - a slug not yet loaded falls back to the plain Config
    // list rather than a broken/nonexistent entity id
    public function configLinks(): array
    {
        $links = [];
        foreach (self::LINKED_SLUGS as $slug) {
            $config = $this->configRepository->findOneBy(['slug' => $slug]);
            $urlGenerator = $this->adminUrlGenerator->unsetAll()->setController(ConfigCrudController::class);
            $links[$slug] = $config
                ? $urlGenerator->setAction(Action::EDIT)->setEntityId($config->getId())->generateUrl()
                : $urlGenerator->setAction(Action::INDEX)->generateUrl();
        }

        return $links;
    }

    // {site-key: token} - ConfigService already returns "donovan-qa-authorized-tokens" json-decoded (and
    // decrypted, since it's sensitive), so no parsing needed here
    public function authorizedTokens(): array
    {
        $tokens = $this->configService->get('donovan-qa-authorized-tokens');

        return \is_array($tokens) ? $tokens : [];
    }
}
