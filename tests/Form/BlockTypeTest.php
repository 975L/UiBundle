<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Form;

use c975L\UiBundle\Form\BlockType;
use c975L\UiBundle\Registry\BlockRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    // "kind" field's choices are restricted per context (e.g. a CollectionField configured with
    // context: 'menu' only offers kinds available in that context) - see BlockRegistry::groupedByCategory()
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

    // A CollectionField that never sets "context" (existing usages, before this option existed) must
    // keep seeing every pickable kind - groupedByCategory(null) applies no context filter
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

    // The actual splicing logic is MultiUploadMerger's own (see MultiUploadMergerTest) - this only
    // verifies BlockType wires it in correctly for a kind that allows multi upload
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
}
