<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Listener;

use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Twig\VideoExtension;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

// The "noCookie" checkbox (VideoIframeType) only expresses editor intent - the actual youtube-nocookie.com
// rewrite happens here, once, before the URL reaches the DB. Iframe.html.twig renders src as-is, so an
// unchecked box keeps whatever URL the editor entered (including a regular, cookie-setting YouTube URL).
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class BlockVideoNoCookieListener
{
    public function __construct(private VideoExtension $videoExtension) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->rewriteSrc($args->getObject());
    }

    // Same recomputeSingleEntityChangeSet() requirement as BlockUserListener::preUpdate() - Doctrine
    // already snapshot the changeset by the time this fires, so a plain setData() call would be dropped
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$this->rewriteSrc($entity)) {
            return;
        }

        $entityManager = $args->getObjectManager();
        $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $entityManager->getClassMetadata($entity::class),
            $entity
        );
    }

    // Rewrites data['src'] in place when noCookie is checked and the host is a recognized YouTube one, returns whether it did
    private function rewriteSrc(object $entity): bool
    {
        if (!$entity instanceof Block || 'video_iframe' !== $entity->getKind()) {
            return false;
        }

        $data = $entity->getData();
        if (empty($data['noCookie']) || empty($data['src'])) {
            return false;
        }

        $rewritten = $this->videoExtension->toPrivacyEmbedUrl($data['src']);
        if ($rewritten === $data['src']) {
            return false;
        }

        $data['src'] = $rewritten;
        $entity->setData($data);

        return true;
    }
}
