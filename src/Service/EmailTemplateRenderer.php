<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Service;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\UiBundle\Entity\EmailBlock;
use c975L\UiBundle\Entity\EmailTemplate;
use c975L\UiBundle\Registry\EmailLayoutRegistry;

// Compiles an EmailTemplate's blocks into one email-safe HTML document (table layout, inline CSS, no JS - see
// templates/emails/blocks/*.html.twig) - kept separate from c975L\UiBundle\Twig\BlockExtension's render_block():
// that one resolves a kind through BlockRegistry (DI-tagged, open-ended), this one resolves EmailBlock::TYPE_*
// through a plain match() since the email-safe vocabulary is deliberately closed, see EmailBlock's own docblock
class EmailTemplateRenderer
{
    public function __construct(
        private readonly \Twig\Environment $twig,
        private readonly ConfigServiceInterface $configService,
        private readonly EmailLayoutRegistry $emailLayoutRegistry,
    ) {
    }

    /**
     * Full standalone document - used by EmailTemplateCrudController's preview and by real EmailTemplate-based
     * sends (e.g. SendEmailFormAction). When an EmailLayoutProviderInterface is registered (e.g. SiteBundle,
     * bringing its own branded header/footer), the body is wrapped through it, so a preview and the actual
     * recipient's inbox render the same way; with none registered, falls back to a bare standalone document
     * (_wrapper.html.twig) - see EmailLayoutRegistry
     *
     * @param array<string, scalar> $variables see renderBody()
     */
    public function render(EmailTemplate $emailTemplate, array $variables = []): string
    {
        $blocksHtml = $this->renderBlocks($emailTemplate, $variables);

        return $this->emailLayoutRegistry->wrap($this->wrapBlocksInTable($blocksHtml))
            ?? $this->twig->render('@c975LUi/emails/blocks/_wrapper.html.twig', ['blocksHtml' => $blocksHtml]);
    }

    /**
     * Just the compiled <tr> rows, wrapped in one <table> but with no surrounding <html>/<body> - meant to be
     * embedded inside an app/bundle's own email layout (e.g. SiteBundle's fullLayout.html.twig, which brings its
     * own Menu-driven header/footer - see c975L\UiBundle\Twig\EmailTemplateExtension::emailTemplateBody() and
     * EmailLayoutProviderInterface, its render()-time equivalent)
     *
     * @param array<string, scalar> $variables resolves "{{ key }}" placeholders found in heading/content/label/url/alt
     *                                          (see substitute() - literal replacement, not real Twig evaluation),
     *                                          plus an optional "fields" array<string, mixed> consumed by any
     *                                          EmailBlock::TYPE_FIELDS_TABLE block (e.g. a Form submission's
     *                                          label => submitted value pairs, see SendEmailFormAction)
     */
    public function renderBody(EmailTemplate $emailTemplate, array $variables = []): string
    {
        return $this->wrapBlocksInTable($this->renderBlocks($emailTemplate, $variables));
    }

    /** @param string[] $blocksHtml */
    private function wrapBlocksInTable(array $blocksHtml): string
    {
        return sprintf(
            '<table role="presentation" width="100%%" cellpadding="0" cellspacing="0" border="0"><tbody>%s</tbody></table>',
            implode('', $blocksHtml)
        );
    }

    /** @return string[] */
    private function renderBlocks(EmailTemplate $emailTemplate, array $variables): array
    {
        $blocksHtml = [];
        foreach ($emailTemplate->getBlocks() as $block) {
            $blocksHtml[] = $this->twig->render($this->templateFor($block->getType()), $this->blockContext($block, $variables));
        }

        return $blocksHtml;
    }

    private function templateFor(string $type): string
    {
        return match ($type) {
            EmailBlock::TYPE_HEADING => '@c975LUi/emails/blocks/heading.html.twig',
            EmailBlock::TYPE_TEXT => '@c975LUi/emails/blocks/text.html.twig',
            EmailBlock::TYPE_BUTTON => '@c975LUi/emails/blocks/button.html.twig',
            EmailBlock::TYPE_IMAGE => '@c975LUi/emails/blocks/image.html.twig',
            EmailBlock::TYPE_DIVIDER => '@c975LUi/emails/blocks/divider.html.twig',
            EmailBlock::TYPE_SPACER => '@c975LUi/emails/blocks/spacer.html.twig',
            EmailBlock::TYPE_FIELDS_TABLE => '@c975LUi/emails/blocks/fields_table.html.twig',
            default => throw new \InvalidArgumentException(sprintf('Unknown EmailBlock type "%s"', $type)),
        };
    }

    private function blockContext(EmailBlock $block, array $variables): array
    {
        $url = $this->substitute($block->getUrl(), $variables);

        return [
            'heading' => $this->substitute($block->getHeading(), $variables),
            'level' => $block->getLevel() ?? EmailBlock::LEVEL_H2,
            'content' => $this->contentToHtml($this->substitute($block->getContent(), $variables)),
            'label' => $this->substitute($block->getLabel(), $variables),
            'url' => EmailBlock::TYPE_IMAGE === $block->getType() ? $this->resolveImageUrl($url) : $url,
            'alt' => $this->substitute($block->getAlt(), $variables),
            'height' => $block->getHeight() ?? 24,
            'fields' => $variables['fields'] ?? [],
        ];
    }

    // A TYPE_IMAGE url may be stored as just a path (e.g. "/medias/logo.webp") instead of a full absolute URL, so
    // the domain lives in one place - the "site-url" ConfigBundle parameter, the same one fullLayout.html.twig
    // itself already builds the logo's src from - rather than being hand-typed into every image block and going
    // stale the day the domain changes. An already-absolute url (http(s):// or protocol-relative // - an
    // external/CDN image) is left as-is
    private function resolveImageUrl(?string $url): ?string
    {
        if (null === $url || '' === $url || 1 === preg_match('#^(https?:)?//#i', $url)) {
            return $url;
        }

        return rtrim((string) $this->configService->get('site-url'), '/') . '/' . ltrim($url, '/');
    }

    // Literal "{{ key }}" replacement, NOT real Twig evaluation - an EmailBlock's text is admin-authored data, not
    // code, so it must never be handed to Twig::createTemplate()/render-from-string (that would open a server-side
    // template injection hole the moment an editor role is over-trusted or compromised)
    private function substitute(?string $raw, array $variables): ?string
    {
        if (null === $raw || [] === $variables) {
            return $raw;
        }

        $map = [];
        foreach ($variables as $key => $value) {
            if (is_scalar($value)) {
                $map['{{ ' . $key . ' }}'] = (string) $value;
            }
        }

        return strtr($raw, $map);
    }

    // Turns plain admin-authored text into safe <p>/<br> markup - escaped here (not left to Twig's own autoescape)
    // so real paragraph tags can be inserted around each blank-line-separated block; the result is trusted HTML
    // from this point on and output with |raw in text.html.twig
    private function contentToHtml(?string $raw): string
    {
        if (null === $raw || '' === trim($raw)) {
            return '';
        }

        $paragraphs = array_filter(
            preg_split('/\n\s*\n/', trim($raw)) ?: [],
            static fn (string $paragraph): bool => '' !== trim($paragraph)
        );

        return implode('', array_map(
            static fn (string $paragraph): string => sprintf(
                '<p style="margin:0 0 12px;">%s</p>',
                nl2br(htmlspecialchars($paragraph, ENT_QUOTES), false)
            ),
            $paragraphs
        ));
    }
}
