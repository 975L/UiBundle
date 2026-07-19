<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Form;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\UiBundle\Entity\FormField;
use c975L\UiBundle\Form\FormSubmissionType;
use c975L\UiBundle\Service\FormBotProtection;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Validator\Constraints\NotBlank;

class FormSubmissionTypeTest extends TestCase
{
    private function buildField(string $name, string $type, bool $required, ?string $placeholder = null): FormField
    {
        $field = new FormField();
        $field->setName($name);
        $field->setLabel(ucfirst($name));
        $field->setType($type);
        $field->setRequired($required);
        $field->setPlaceholder($placeholder);

        return $field;
    }

    private function createType(bool $gdpr = false, bool $recaptcha = false): FormSubmissionType
    {
        $configService = $this->createStub(ConfigServiceInterface::class);
        $configService->method('hasParameter')->willReturnMap([
            ['recaptcha3-site-key', $recaptcha],
            ['recaptcha3-secret-key', $recaptcha],
        ]);
        $configService->method('get')->willReturnMap([
            ['recaptcha3-site-key', $recaptcha ? 'site-key' : null],
            ['recaptcha3-secret-key', $recaptcha ? 'secret-key' : null],
            ['site-form-gdpr', $gdpr],
        ]);
        $configService->method('getContainerParameter')->willReturn(null);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new FormSubmissionType(new FormBotProtection($configService), $configService, $requestStack);
    }

    private function buildAddedFields(array $fields, bool $offerReceiveCopy = false, bool $gdpr = false, bool $recaptcha = false, array $prefill = []): array
    {
        $added = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(function (string $name, ?string $type = null, array $options = []) use (&$added, $builder) {
            $added[$name] = ['type' => $type, 'options' => $options];

            return $builder;
        });

        $this->createType($gdpr, $recaptcha)->buildForm($builder, ['fields' => $fields, 'offerReceiveCopy' => $offerReceiveCopy, 'prefill' => $prefill]);

        return $added;
    }

    public function testEachFieldTypeMapsToItsSymfonyFormType(): void
    {
        $added = $this->buildAddedFields([
            $this->buildField('name', FormField::TYPE_TEXT, false),
            $this->buildField('message', FormField::TYPE_TEXTAREA, false),
            $this->buildField('email', FormField::TYPE_EMAIL, false),
            $this->buildField('newsletter', FormField::TYPE_CHECKBOX, false),
        ]);

        $this->assertSame(TextType::class, $added['name']['type']);
        $this->assertSame(TextareaType::class, $added['message']['type']);
        $this->assertSame(EmailType::class, $added['email']['type']);
        $this->assertSame(CheckboxType::class, $added['newsletter']['type']);
    }

    public function testRequiredFieldGetsNotBlankConstraint(): void
    {
        $added = $this->buildAddedFields([$this->buildField('email', FormField::TYPE_EMAIL, true)]);

        $this->assertTrue($added['email']['options']['required']);
        $this->assertCount(1, $added['email']['options']['constraints']);
        $this->assertInstanceOf(NotBlank::class, $added['email']['options']['constraints'][0]);
    }

    public function testOptionalFieldGetsNoConstraint(): void
    {
        $added = $this->buildAddedFields([$this->buildField('phone', FormField::TYPE_TEXT, false)]);

        $this->assertFalse($added['phone']['options']['required']);
        $this->assertSame([], $added['phone']['options']['constraints']);
    }

    public function testPlaceholderIsPassedAsAttrWhenSet(): void
    {
        $added = $this->buildAddedFields([$this->buildField('phone', FormField::TYPE_TEXT, false, '06...')]);

        $this->assertSame(['placeholder' => '06...'], $added['phone']['options']['attr']);
    }

    public function testLabelIsNeverTranslated(): void
    {
        $added = $this->buildAddedFields([$this->buildField('phone', FormField::TYPE_TEXT, false)]);

        $this->assertFalse($added['phone']['options']['translation_domain']);
        $this->assertSame('Phone', $added['phone']['options']['label']);
    }

    // Honeypot is unconditional - no config needed, same as contact/register/reset. With no fields/gdpr/recaptcha/receiveCopy, it's the only field added
    public function testHoneypotFieldIsAlwaysAdded(): void
    {
        $added = $this->buildAddedFields([]);

        $this->assertCount(1, $added);
    }

    public function testReceiveCopyFieldAddedOnlyWhenOffered(): void
    {
        $this->assertArrayNotHasKey('receiveCopy', $this->buildAddedFields([], offerReceiveCopy: false));

        $added = $this->buildAddedFields([], offerReceiveCopy: true);
        $this->assertSame(CheckboxType::class, $added['receiveCopy']['type']);
        $this->assertFalse($added['receiveCopy']['options']['required']);
    }

    public function testGdprFieldAddedOnlyWhenConfigured(): void
    {
        $this->assertArrayNotHasKey('gdpr', $this->buildAddedFields([], gdpr: false));

        $added = $this->buildAddedFields([], gdpr: true);
        $this->assertSame(CheckboxType::class, $added['gdpr']['type']);
        $this->assertTrue($added['gdpr']['options']['required']);
    }

    public function testRecaptchaFieldAddedOnlyWhenBothKeysConfigured(): void
    {
        $this->assertArrayNotHasKey('captcha', $this->buildAddedFields([], recaptcha: false));

        $added = $this->buildAddedFields([], recaptcha: true);
        $this->assertSame(Recaptcha3Type::class, $added['captcha']['type']);
    }

    // nelmio/security-bundle isn't installed here (a soft/optional dependency, like ContactFormBundle's own ContactFormFactory) - $cspListener stays null, so the nonce is null too rather than erroring
    public function testRecaptchaFieldHasNoNonceWhenCspListenerNotAvailable(): void
    {
        $added = $this->buildAddedFields([], recaptcha: true);

        $this->assertArrayHasKey('script_nonce_csp', $added['captcha']['options']);
        $this->assertNull($added['captcha']['options']['script_nonce_csp']);
    }

    // See FormPrefillHelper - a field matching a prefill key gets its value as initial data and becomes readonly (still submitted, just not editable), same UX as ContactFormBundle's own locked "subject" field
    public function testPrefilledFieldGetsDataAndReadonlyAttr(): void
    {
        $added = $this->buildAddedFields(
            [$this->buildField('subject', FormField::TYPE_TEXT, false)],
            prefill: ['subject' => 'About listing #42']
        );

        $this->assertSame('About listing #42', $added['subject']['options']['data']);
        $this->assertTrue($added['subject']['options']['attr']['readonly']);
    }

    public function testNonPrefilledFieldHasNoDataOrReadonlyAttr(): void
    {
        $added = $this->buildAddedFields(
            [$this->buildField('subject', FormField::TYPE_TEXT, false)],
            prefill: ['other' => 'ignored']
        );

        $this->assertArrayNotHasKey('data', $added['subject']['options']);
        $this->assertArrayNotHasKey('readonly', $added['subject']['options']['attr']);
    }
}
