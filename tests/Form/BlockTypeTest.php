<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Form;

use c975L\UiBundle\Entity\Block;
use c975L\UiBundle\Form\BlockType;
use c975L\UiBundle\Registry\BlockRegistry;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Count;

class BlockTypeTest extends TestCase
{
    private function createRouter(): UrlGeneratorInterface
    {
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/ui/block/data-form');

        return $router;
    }

    // Captures every builder->add() call's options, so the "kind" field's "choices" can be asserted
    private function buildAddedOptions(BlockType $type, array $options): array
    {
        $added = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(function (string $name, ?string $fieldType = null, array $fieldOptions = []) use (&$added, $builder) {
            $added[$name] = $fieldOptions;

            return $builder;
        });
        $builder->method('addEventListener')->willReturnSelf();

        $type->buildForm($builder, $options);

        return $added;
    }

    public function testConfigureOptionsDefaultsContextToNull(): void
    {
        $type = new BlockType($this->createStub(BlockRegistry::class), $this->createRouter());
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertNull($options['context']);
    }

    // "kind" field's choices are restricted per context (e.g. a CollectionField configured with context: 'menu' only offers kinds available in that context) - see BlockRegistry::groupedByCategory()
    public function testBuildFormPassesTheContextOptionToGroupedByCategory(): void
    {
        $registry = $this->createMock(BlockRegistry::class);
        $registry->expects($this->once())
            ->method('groupedByCategory')
            ->with('menu')
            ->willReturn(['Navigation' => ['Menu link' => 'menu_link']]);

        $type = new BlockType($registry, $this->createRouter());
        $added = $this->buildAddedOptions($type, ['context' => 'menu']);

        $this->assertSame(['Navigation' => ['Menu link' => 'menu_link']], $added['kind']['choices']);
    }

    // A CollectionField that never sets "context" (existing usages, before this option existed) must keep seeing every pickable kind - groupedByCategory(null) applies no context filter
    public function testBuildFormWithNoContextPassesNullToGroupedByCategory(): void
    {
        $registry = $this->createMock(BlockRegistry::class);
        $registry->expects($this->once())->method('groupedByCategory')->with(null)->willReturn([]);

        $type = new BlockType($registry, $this->createRouter());
        $this->buildAddedOptions($type, ['context' => null]);
    }

    private function invokeMergeMultiUpload(BlockType $type, array $submitted, string $kind): array
    {
        return (new \ReflectionMethod($type, 'mergeMultiUpload'))->invoke($type, $submitted, $kind);
    }

    // Captures every form->add() call's options fired by the private addMediaSubForm(), so the "medias" field's "constraints" can be asserted
    private function buildAddedMediaOptions(BlockType $type, string $kind): array
    {
        $added = [];
        $form = $this->createStub(FormInterface::class);
        $form->method('add')->willReturnCallback(function (string $name, ?string $fieldType = null, array $fieldOptions = []) use (&$added, $form) {
            $added[$name] = $fieldOptions;

            return $form;
        });

        (new \ReflectionMethod($type, 'addMediaSubForm'))->invoke($type, $form, $kind);

        return $added;
    }

    private function createUploadedFile(string $originalName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ui-block-type-test-');

        return new UploadedFile($path, $originalName, null, null, true);
    }

    public function testMergeMultiUploadAlwaysRemovesTheMediaUploadKey(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('allowsMultiUpload')->willReturn(false);
        $type = new BlockType($registry, $this->createRouter());

        $result = $this->invokeMergeMultiUpload($type, ['medias' => [], 'mediaUpload' => null], 'article');

        $this->assertArrayNotHasKey('mediaUpload', $result);
    }

    public function testMergeMultiUploadLeavesMediasUnchangedWhenKindDoesNotAllowIt(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('allowsMultiUpload')->willReturn(false);
        $type = new BlockType($registry, $this->createRouter());

        $file = $this->createUploadedFile('a.jpg');
        $result = $this->invokeMergeMultiUpload($type, ['medias' => ['x'], 'mediaUpload' => [$file]], 'article');

        $this->assertSame(['x'], $result['medias']);
    }

    public function testMergeMultiUploadLeavesMediasUnchangedWhenNoFilesWereSubmitted(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('allowsMultiUpload')->willReturn(true);
        $type = new BlockType($registry, $this->createRouter());

        $result = $this->invokeMergeMultiUpload($type, ['medias' => ['x']], 'slider');

        $this->assertSame(['x'], $result['medias']);
    }

    // The actual splicing logic is MultiUploadMerger's own (see MultiUploadMergerTest) - this only verifies BlockType wires it in correctly for a kind that allows multi upload
    public function testMergeMultiUploadSplicesSubmittedFilesIntoMediasWhenKindAllowsIt(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('allowsMultiUpload')->willReturn(true);
        $type = new BlockType($registry, $this->createRouter());

        $file = $this->createUploadedFile('a.jpg');
        $result = $this->invokeMergeMultiUpload($type, ['medias' => [], 'mediaUpload' => [$file]], 'slider');

        $this->assertArrayNotHasKey('mediaUpload', $result);
        $this->assertCount(1, $result['medias']);
        $this->assertSame($file, $result['medias'][0]['file']['file']);
    }

    // "hero"'s pure-CSS crossfade only has slide rules for up to 6 images (see sass/_page-sections.scss) - this caps the field so an editor can't silently attach a 7th that would collide with an earlier slide
    public function testAddMediaSubFormCapsHeroMediaCountWithACountConstraint(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('getMediaTypes')->willReturn(['image/*']);
        $registry->method('allowsMultiUpload')->willReturn(true);
        $type = new BlockType($registry, $this->createRouter());

        $added = $this->buildAddedMediaOptions($type, 'hero');

        $this->assertCount(1, $added['medias']['constraints']);
        $this->assertInstanceOf(Count::class, $added['medias']['constraints'][0]);
        $this->assertSame(6, $added['medias']['constraints'][0]->max);
    }

    public function testAddMediaSubFormAddsNoConstraintsForKindsOtherThanHero(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('getMediaTypes')->willReturn(['image/*']);
        $registry->method('allowsMultiUpload')->willReturn(false);
        $type = new BlockType($registry, $this->createRouter());

        $added = $this->buildAddedMediaOptions($type, 'article');

        $this->assertSame([], $added['medias']['constraints']);
    }

    // The "medias" field's help text is whatever BlockRegistry::getMediaHelp() declares for the kind (see BlockRegistryTest for the kind-specific-vs-generic logic itself) - addMediaSubForm() just wires it through
    public function testAddMediaSubFormUsesTheRegistrysDeclaredMediaHelpText(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('getMediaTypes')->willReturn(['application/pdf']);
        $registry->method('getMediaHelp')->willReturn('label.document_download_media_help');
        $type = new BlockType($registry, $this->createRouter());

        $added = $this->buildAddedMediaOptions($type, 'document_download');

        $this->assertSame('label.document_download_media_help', $added['medias']['help']);
    }

    // A container kind's (e.g. "flex_columns") "slots" field is a CollectionType of BlockType itself,
    // recursively, scoped to whatever BlockRegistry::getSlotContext($kind) declares for that kind - see
    // addSlotsSubForm()
    public function testAddSlotsSubFormAddsACollectionOfBlockTypeScopedToTheKindsOwnSlotContext(): void
    {
        $registry = $this->createMock(BlockRegistry::class);
        $registry->expects($this->once())->method('getSlotContext')->with('flex_columns')->willReturn(BlockRegistry::SLOT_CONTEXT);
        $type = new BlockType($registry, $this->createRouter());

        $added = [];
        $form = $this->createStub(FormInterface::class);
        $form->method('add')->willReturnCallback(function (string $name, ?string $fieldType = null, array $fieldOptions = []) use (&$added, $form) {
            $added[$name] = ['type' => $fieldType, 'options' => $fieldOptions];

            return $form;
        });

        (new \ReflectionMethod($type, 'addSlotsSubForm'))->invoke($type, $form, 'flex_columns', null);

        $this->assertSame(CollectionType::class, $added['slots']['type']);
        $this->assertSame(BlockType::class, $added['slots']['options']['entry_type']);
        $this->assertSame(BlockRegistry::SLOT_CONTEXT, $added['slots']['options']['entry_options']['context']);
        $this->assertTrue($added['slots']['options']['allow_add']);
        $this->assertTrue($added['slots']['options']['allow_delete']);
        $this->assertSame('label.slots', $added['slots']['options']['label']);
    }

    // "section_cards" gets its own "Cards" label instead of the generic "flex_columns" one, since the
    // two containers share the same addSlotsSubForm() mechanism but not the same slots field wording
    public function testAddSlotsSubFormUsesADedicatedLabelForSectionCards(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('getSlotContext')->willReturn(BlockRegistry::SLOT_CONTEXT);
        $type = new BlockType($registry, $this->createRouter());

        $added = [];
        $form = $this->createStub(FormInterface::class);
        $form->method('add')->willReturnCallback(function (string $name, ?string $fieldType = null, array $fieldOptions = []) use (&$added, $form) {
            $added[$name] = ['type' => $fieldType, 'options' => $fieldOptions];

            return $form;
        });

        (new \ReflectionMethod($type, 'addSlotsSubForm'))->invoke($type, $form, 'section_cards', null);

        $this->assertSame('label.slots_cards', $added['slots']['options']['label']);
    }

    // No container Block passed in (a brand new, not-yet-persisted one) - the "slots" field must not be
    // marked as a Block collection at all, since there's no container id yet to relocate anything against
    // (see BlockMoveController) - and this method must never call $form->getData() itself to find out,
    // that would throw Symfony's "cycle detected" error when called from BlockType's own PRE_SET_DATA
    public function testAddSlotsSubFormOmitsRowAttrWhenTheContainerIsNotYetPersisted(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('getSlotContext')->willReturn(BlockRegistry::SLOT_CONTEXT);
        $type = new BlockType($registry, $this->createRouter());

        $added = [];
        $form = $this->createStub(FormInterface::class);
        $form->method('add')->willReturnCallback(function (string $name, ?string $fieldType = null, array $fieldOptions = []) use (&$added, $form) {
            $added[$name] = ['type' => $fieldType, 'options' => $fieldOptions];

            return $form;
        });

        (new \ReflectionMethod($type, 'addSlotsSubForm'))->invoke($type, $form, 'flex_columns', null);

        $this->assertSame([], $added['slots']['options']['row_attr']);
    }

    // A real, already-persisted container - "slots" gets marked as a Block collection, carrying this
    // container's own id, for ea-sortable.js/BlockMoveController to relocate a block into it
    public function testAddSlotsSubFormAddsRowAttrWithTheContainersOwnIdWhenPersisted(): void
    {
        $registry = $this->createStub(BlockRegistry::class);
        $registry->method('getSlotContext')->willReturn(BlockRegistry::SLOT_CONTEXT);
        $type = new BlockType($registry, $this->createRouter());

        $container = new Block();
        (new \ReflectionProperty(Block::class, 'id'))->setValue($container, 42);

        $added = [];
        $form = $this->createStub(FormInterface::class);
        $form->method('add')->willReturnCallback(function (string $name, ?string $fieldType = null, array $fieldOptions = []) use (&$added, $form) {
            $added[$name] = ['type' => $fieldType, 'options' => $fieldOptions];

            return $form;
        });

        (new \ReflectionMethod($type, 'addSlotsSubForm'))->invoke($type, $form, 'flex_columns', $container);

        $this->assertSame('1', $added['slots']['options']['row_attr']['data-block-collection']);
        $this->assertSame(42, $added['slots']['options']['row_attr']['data-block-container-id']);
    }

    // "flex_column" (a nested container) declares its own slots with NESTED_SLOT_CONTEXT instead, so its
    // own elements can't in turn offer another "flex_column" - addSlotsSubForm() must reflect whatever the
    // registry says for the given kind, not a single hardcoded context
    public function testAddSlotsSubFormUsesTheKindsDeclaredSlotContext(): void
    {
        $registry = $this->createMock(BlockRegistry::class);
        $registry->expects($this->once())->method('getSlotContext')->with('flex_column')->willReturn(BlockRegistry::NESTED_SLOT_CONTEXT);
        $type = new BlockType($registry, $this->createRouter());

        $added = [];
        $form = $this->createStub(FormInterface::class);
        $form->method('add')->willReturnCallback(function (string $name, ?string $fieldType = null, array $fieldOptions = []) use (&$added, $form) {
            $added[$name] = ['type' => $fieldType, 'options' => $fieldOptions];

            return $form;
        });

        (new \ReflectionMethod($type, 'addSlotsSubForm'))->invoke($type, $form, 'flex_column', null);

        $this->assertSame(BlockRegistry::NESTED_SLOT_CONTEXT, $added['slots']['options']['entry_options']['context']);
    }
}
