<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Service;

use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Entity\Media;
use c975L\UiBundle\Registry\BlockRegistry;

// Attaches placeholder media (Laurent Marquet's own photos/video/audio, shipped as static bundle assets -
// see public/images, public/videos, public/audio) to an in-memory, never-persisted Block, standing in for
// whatever real media a kind's own fixture doesn't carry. Public service, not app-specific logic: shared
// by any consuming app's own public showcase (e.g. 975l.com's /vitrine-blocks) so it gets real-looking
// previews without needing app-specific media rows (this replaced 975l.com's own block_showcase_media.json).
class BlockFixtureMediaAttacher
{
    // A small pool rather than one photo per kind: nextPlaceholderImage() hands them out in rotation (see
    // $photoCursor) so kinds shown one after another on the same page don't all repeat the same photo, while
    // image_compare/portfolio_grid/slider naturally get distinct ones too, with no special-casing needed.
    public const PLACEHOLDER_IMAGES = [
        'bundles/c975lui/images/gallery-photo-1.webp',
        'bundles/c975lui/images/gallery-photo-2.webp',
        'bundles/c975lui/images/gallery-photo-3.webp',
        'bundles/c975lui/images/gallery-photo-4.webp',
        'bundles/c975lui/images/gallery-photo-5.webp',
    ];
    public const PLACEHOLDER_VIDEO = 'bundles/c975lui/videos/gallery-video.mp4';
    public const PLACEHOLDER_AUDIO = 'bundles/c975lui/audio/gallery-audio.mp3';
    public const PLACEHOLDER_DOCUMENT = 'bundles/c975lui/documents/gallery-document.pdf';

    // "video_iframe" just embeds any URL in a plain <iframe> (see Video/Iframe.html.twig) - browsers show
    // their own native player for a direct file navigation, autoplaying with sound by default. This tiny
    // static HTML wrapper (a <video autoplay muted loop>) is used instead of PLACEHOLDER_VIDEO directly,
    // so the gallery/showcase preview doesn't play audio on its own.
    public const PLACEHOLDER_VIDEO_EMBED = 'bundles/c975lui/videos/gallery-video-embed.html';

    // Advanced by nextPlaceholderImage() - reset() it at the start of every request/loop building several
    // blocks, so the rotation restarts at the same photo every time instead of drifting with instance reuse
    private int $photoCursor = 0;

    public function __construct(private readonly BlockRegistry $registry)
    {
    }

    public function reset(): void
    {
        $this->photoCursor = 0;
    }

    // Attaches whichever placeholder media $kind's own media_types call for - image(s), a video, an audio
    // clip, or (for portfolio_grid) several distinctly-captioned project cards. No-op for a kind with no
    // media_types at all. $variant lets a specific fixture variant (e.g. slider's "freeflow") ask for a
    // different image count than its kind's own default - see imageCount().
    public function attach(Block $block, string $kind, string $variant = ''): void
    {
        if ('portfolio_grid' === $kind) {
            foreach ($this->placeholderPortfolioProjects() as $project) {
                $block->addMedia($project);
            }

            return;
        }

        foreach ($this->registry->getMediaTypes($kind) as $mediaType) {
            if (str_starts_with($mediaType, 'image/')) {
                $count = $this->imageCount($kind, $variant);
                for ($i = 0; $i < $count; ++$i) {
                    $block->addMedia($this->nextPlaceholderImage());
                }
            }

            // Any kind whose own media_types include video/* gets a video slide mixed in - not just
            // "slider" - skipped for "freeflow" (already busy demonstrating its own layout with more
            // images, see imageCount())
            if ('freeflow' !== $variant && str_starts_with($mediaType, 'video/')) {
                $block->addMedia($this->placeholderVideo());
            }

            if (str_starts_with($mediaType, 'audio/')) {
                $block->addMedia($this->placeholderAudio());
                break;
            }

            if ('application/pdf' === $mediaType) {
                $block->addMedia($this->placeholderDocument());
                break;
            }
        }
    }

    // How many placeholder images a kind (and, for slider, its variant) needs to look like itself:
    // - "freeflow" needs enough slides to actually demonstrate its distinct scrolling layout (3 wasn't
    //   enough - Laurent)
    // - "image_compare" always needs exactly 2 (before/after) - a fixed pair, not an open-ended
    //   "several", so it stays its own case rather than going through allowsMultiUpload() below
    // - "article" wants 3 - Laurent's call, more than the generic multi-upload default
    // - any other kind tagged media_multi_upload (article/slider/portfolio_grid... - see services.yaml)
    //   gets a generic "several" count, so a third-party multi-upload kind benefits automatically
    //   without UiBundle needing to know its name
    // - everything else only ever reads its first media (see e.g. blocks/Image.html.twig)
    private function imageCount(string $kind, string $variant): int
    {
        if ('slider' === $kind && 'freeflow' === $variant) {
            return 5;
        }

        if ('image_compare' === $kind) {
            return 2;
        }

        if ('article' === $kind) {
            return 3;
        }

        return $this->registry->allowsMultiUpload($kind) ? 2 : 1;
    }

    // @return Media[]
    private function placeholderPortfolioProjects(): array
    {
        // Generic client-project copy, not a real 975l.com portfolio - same reasoning as
        // GalleryShowcaseProvider's own "collection" fixture. "#" (not a real 975l.com URL) since this
        // is rendered by any consuming app's own showcase, not just 975l.com's.
        $projects = [
            ['Refonte e-commerce', 'Une boutique en ligne repensée pour la conversion, développée sur mesure avec Symfony.'],
            ['Application SaaS', 'Une plateforme métier sur mesure, de la conception à la mise en production.'],
            ['Site vitrine', 'Un site rapide, accessible et facile à maintenir, sans usine à gaz.'],
        ];

        return array_map(
            fn (array $project): Media => $this->nextPlaceholderImage()
                ->setAlt($project[0])
                ->setLabel($project[0])
                ->setDescription($project[1])
                ->setUrl('#'),
            $projects
        );
    }

    // Public: also used directly by callers building a placeholder image outside of attach()'s own
    // media_types-driven logic (e.g. a GalleryShowcaseProviderInterface implementation feeding a few
    // placeholder images into its own showcase preview)
    public function nextPlaceholderImage(): Media
    {
        $filename = self::PLACEHOLDER_IMAGES[$this->photoCursor % count(self::PLACEHOLDER_IMAGES)];
        ++$this->photoCursor;

        return (new Media())
            ->setFilename($filename)
            ->setAlt('Photo d\'exemple');
    }

    private function placeholderVideo(): Media
    {
        return (new Media())
            ->setFilename(self::PLACEHOLDER_VIDEO)
            ->setMimeType('video/mp4')
            ->setAlt('Vidéo d\'exemple');
    }

    private function placeholderAudio(): Media
    {
        return (new Media())
            ->setFilename(self::PLACEHOLDER_AUDIO)
            ->setMimeType('audio/mpeg')
            ->setAlt('Audio d\'exemple');
    }

    private function placeholderDocument(): Media
    {
        return (new Media())
            ->setFilename(self::PLACEHOLDER_DOCUMENT)
            ->setMimeType('application/pdf')
            ->setAlt('Document d\'exemple');
    }
}
