<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Service;

use c975L\UiBundle\Contract\BlockFixtureProviderInterface;
use c975L\UiBundle\Controller\Management\BlockGalleryController;

// Sample data for UiBundle's own built-in block kinds, shown in the block gallery (see
// BlockGalleryController).
class BlockFixtureProvider implements BlockFixtureProviderInterface
{
    public function getFixtures(): array
    {
        return [
            // Every choice of AlertType::$choices, so an editor can compare all four at a glance
            'alert' => [
                'Info' => [
                    'type' => 'info',
                    'content' => '<p>Ceci est un exemple de message d\'information.</p>',
                ],
                'Succès' => [
                    'type' => 'success',
                    'content' => '<p>Ceci est un exemple de message de succès.</p>',
                ],
                'Avertissement' => [
                    'type' => 'warning',
                    'content' => '<p>Ceci est un exemple de message d\'avertissement.</p>',
                ],
                'Danger' => [
                    'type' => 'danger',
                    'content' => '<p>Ceci est un exemple de message de danger.</p>',
                ],
            ],
            'audio' => [
                '' => [
                    'type' => 'audio/mpeg',
                ],
            ],
            'article' => [
                '' => [
                    'title' => 'Titre de l\'article',
                    'hook' => '<p>Chapô d\'accroche de l\'article.</p>',
                    'content' => '<p>Contenu de l\'article, avec un peu de texte pour illustrer le rendu.</p>',
                    'slug' => 'titre-de-larticle',
                ],
            ],
            'banner_title' => [
                '' => [
                    'title' => 'Titre de la bannière',
                    'level' => 'h1',
                    'maxHeight' => 400,
                ],
            ],
            // Every choice of ButtonType::$choices, so an editor can compare all five styles at a glance
            'button' => [
                'Primaire' => ['label' => 'Primaire', 'url' => 'https://975l.com', 'type' => 'primary', 'target' => '', 'icon' => '', 'download' => false, 'inline' => false],
                'Secondaire' => ['label' => 'Secondaire', 'url' => 'https://975l.com', 'type' => 'secondary', 'target' => '', 'icon' => '', 'download' => false, 'inline' => false],
                'Succès' => ['label' => 'Succès', 'url' => 'https://975l.com', 'type' => 'success', 'target' => '', 'icon' => '', 'download' => false, 'inline' => false],
                'Danger' => ['label' => 'Danger', 'url' => 'https://975l.com', 'type' => 'danger', 'target' => '', 'icon' => '', 'download' => false, 'inline' => false],
                'Lien' => ['label' => 'Lien', 'url' => 'https://975l.com', 'type' => 'link', 'target' => '', 'icon' => '', 'download' => false, 'inline' => false],
            ],
            'card' => [
                '' => [
                    'id' => '',
                    'title' => 'Titre de la carte',
                    'level' => 'h3',
                    'content' => '<p>Description courte de la carte.</p>',
                    'url' => 'https://975l.com',
                    'target' => '',
                    'buttonLabel' => 'Découvrir',
                    'class' => [],
                ],
            ],
            'image' => [
                '' => [],
            ],
            'progress_bar' => [
                '' => [
                    'progressPercent' => 65,
                    'text' => true,
                ],
            ],
            'rich_snippet' => [
                '' => [
                    'itemscope' => 'https://schema.org/LocalBusiness',
                    'name' => 'Mon Entreprise',
                    'description' => '<p>Une entreprise fictive utilisée comme exemple.</p>',
                    'telephone' => '+33 1 23 45 67 89',
                    'image' => '',
                    'openingHours' => 'Mo-Fr 09:00-18:00',
                    'priceRange' => '€€',
                    'addressStreetAddress' => '1 rue de l\'Exemple',
                    'addressPostalCode' => '75000',
                    'addressAddressLocality' => 'Paris',
                    'addressAddressCountryCode' => 'FR',
                    'addressAddressCountryName' => 'France',
                ],
            ],
            'slider' => [
                '' => [
                    'id' => 'gallery-slider-preview',
                    'duration' => 5000,
                    'ratio' => 'free',
                    'layout' => 'default',
                    'class' => [],
                ],
                'freeflow' => [
                    'id' => 'gallery-slider-freeflow-preview',
                    'duration' => 5000,
                    'ratio' => 'free',
                    'layout' => 'freeflow',
                    'class' => [],
                ],
            ],
            'text_readmore' => [
                '' => [
                    'id' => 'readmore-exemple',
                    'text' => '<p>Texte replié, cliquez pour en savoir plus...</p>',
                ],
            ],
            'text_section' => [
                '' => [
                    'title' => 'Titre de section',
                    'slug' => 'titre-de-section',
                    'content' => '<p>Contenu de la section.</p>',
                    'image' => '',
                ],
            ],
            'video' => [
                '' => [
                    'src' => BlockGalleryController::PLACEHOLDER_VIDEO,
                    'type' => 'video/mp4',
                    'poster' => '',
                    'autoplay' => false,
                    'muted' => true,
                    'loop' => false,
                    'width' => '',
                    'height' => '',
                ],
            ],
            // Its "src" is any URL rendered directly in an <iframe> (see Video/Iframe.html.twig) - not
            // limited to a YouTube/Vimeo-style embed, so the same self-hosted placeholder as "video"
            // works fine here too (browsers show their native player for a media file in an iframe)
            'video_iframe' => [
                '' => [
                    'src' => BlockGalleryController::PLACEHOLDER_VIDEO,
                    'width' => '560',
                    'height' => '315',
                    'class' => [],
                ],
            ],
            'hero' => [
                '' => [
                    'badge' => 'Agence web indépendante · depuis 2018',
                    'title' => 'Votre site web, fait main et <em>sur mesure.</em>',
                    'subtitle' => 'On ne pose pas de template. On code proprement, avec notre propre CMS et Symfony, un site pensé pour vous.',
                    'primaryLabel' => 'Parlons de votre projet',
                    'primaryUrl' => 'https://975l.com/contact',
                    'secondaryLabel' => 'Voir nos réalisations',
                    'secondaryUrl' => 'https://975l.com/sites',
                    'statValue' => '12+',
                    'statLabel' => 'sites en ligne, et le vôtre bientôt',
                ],
            ],
            'feature_bar' => [
                '' => [
                    'items' => [
                        ['title' => 'Code propre', 'text' => 'maintenable dans le temps'],
                        ['title' => 'Symfony', 'text' => 'socle robuste & éprouvé'],
                        ['title' => 'Notre CMS', 'text' => 'édition simple, à vous'],
                        ['title' => 'Suisse', 'text' => 'hébergé chez Infomaniak'],
                        ['title' => 'Multilingue', 'text' => "prêt pour l'international"],
                    ],
                ],
            ],
            'section_cards' => [
                '' => [
                    'eyebrow' => 'Ce que nous faisons',
                    'title' => "Des services taillés autour de votre projet, pas l'inverse.",
                    'cards' => [
                        ['icon' => '', 'title' => 'Sites sur mesure', 'text' => '<p>Chaque site est conçu et développé pour vos besoins réels.</p>'],
                        ['icon' => '', 'title' => 'Notre CMS maison', 'text' => '<p>Vous gérez votre contenu vous-même, simplement.</p>'],
                        ['icon' => '', 'title' => 'Développement Symfony', 'text' => '<p>Un socle technique solide et durable.</p>'],
                    ],
                ],
            ],
            'expertise_banner' => [
                '' => [
                    'eyebrow' => 'Notre expertise',
                    'title' => 'Une technique que nous maîtrisons de bout en bout.',
                    'text' => "<p>Pas de sous-traitance, pas de boîte noire. Nous développons, hébergeons et faisons évoluer votre site.</p>",
                    'tags' => ['Symfony', 'PHP', 'CMS 975L', 'Multilingue', 'Infomaniak · CH', 'Open-source'],
                ],
            ],
            'process_steps' => [
                '' => [
                    'eyebrow' => 'Comment ça se passe',
                    'title' => "Une méthode claire, du premier échange à la mise en ligne.",
                    'steps' => [
                        ['title' => "On s'écoute", 'text' => '<p>On étudie votre problématique ensemble.</p>'],
                        ['title' => 'On conçoit', 'text' => '<p>Structure, design et contenu, validés avec vous.</p>'],
                        ['title' => 'On développe', 'text' => "<p>Code propre, testé, et votre CMS prêt à l'emploi.</p>"],
                        ['title' => 'On accompagne', 'text' => '<p>Mise en ligne, hébergement et suivi dans la durée.</p>'],
                    ],
                ],
            ],
            'portfolio_grid' => [
                '' => [
                    'eyebrow' => 'Réalisations',
                    'title' => 'Des projets en ligne, bien réels.',
                    'linkLabel' => 'Tout voir',
                    'linkUrl' => 'https://975l.com/sites',
                ],
            ],
            'cta_band' => [
                '' => [
                    'title' => 'Racontez-nous votre projet.',
                    'text' => '<p>On étudie votre problématique et on voit ensemble comment y répondre au mieux.</p>',
                    'ctaLabel' => 'Contactez-nous',
                    'ctaUrl' => 'https://975l.com/contact',
                ],
            ],
        ];
    }
}
