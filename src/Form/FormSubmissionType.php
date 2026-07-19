<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Form;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\UiBundle\Entity\FormField;
use c975L\UiBundle\Service\FormBotProtection;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Nelmio\SecurityBundle\EventListener\ContentSecurityPolicyListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;

// Builds a plain Symfony form from a c975L\UiBundle\Entity\Form's FormField collection - one input per field, keyed by FormField::getName(), unmapped to any entity (see FormController, which hands the submitted array straight to FormActionRegistry). Also adds the same protections every c975L bundle's own public forms already share: honeypot (always), GDPR/recaptcha (site-wide config, same keys contact/register/reset already read), receive-copy (per-Form, see Form::$actionConfig's "offerReceiveCopy")
class FormSubmissionType extends AbstractType
{
    public function __construct(
        private readonly FormBotProtection $botProtection,
        private readonly ConfigServiceInterface $configService,
        private readonly RequestStack $requestStack,
        // Optional: only present if nelmio/security-bundle is installed and registered - without it, recaptcha's inline script has no nonce to match a strict CSP, exactly like ContactFormBundle's own ContactFormFactory/ContactFormType already handle it
        private readonly ?ContentSecurityPolicyListener $cspListener = null,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->botProtection->addHoneypotField($builder, $this->requestStack->getCurrentRequest());

        foreach ($options['fields'] as $field) {
            $required = $field->isRequired();
            $prefilled = array_key_exists($field->getName(), $options['prefill']);
            // "translation_domain" false: a field's label is text the admin typed directly (see FormFieldType), not a translation key
            $fieldOptions = [
                'label' => $field->getLabel(),
                'translation_domain' => false,
                'required' => $required,
                'constraints' => $required ? [new NotBlank()] : [],
                // "readonly" (not "disabled"): a prefilled field still gets submitted along with the rest, it just can't be edited - see FormPrefillHelper
                'attr' => array_filter(['placeholder' => $field->getPlaceholder(), 'readonly' => $prefilled ?: null]),
            ];
            if ($prefilled) {
                $fieldOptions['data'] = $options['prefill'][$field->getName()];
            }
            $builder->add($field->getName(), $this->resolveFieldType($field->getType()), $fieldOptions);
        }

        if ($options['offerReceiveCopy']) {
            $builder->add('receiveCopy', CheckboxType::class, [
                'label' => 'label.receive_copy',
                'required' => false,
                'mapped' => false,
                'data' => false,
            ]);
        }

        $recaptchaSiteKey = $this->configService->hasParameter('recaptcha3-site-key') ? $this->configService->get('recaptcha3-site-key') : $this->configService->getContainerParameter('karser_recaptcha3.site_key');
        $recaptchaSecretKey = $this->configService->hasParameter('recaptcha3-secret-key') ? $this->configService->get('recaptcha3-secret-key') : $this->configService->getContainerParameter('karser_recaptcha3.secret_key');
        if ($recaptchaSiteKey && $recaptchaSecretKey) {
            $builder->add('captcha', Recaptcha3Type::class, [
                'action_name' => 'ui_form',
                'script_nonce_csp' => $this->cspListener?->getNonce('script'),
            ]);
        }

        // Falls back to true if "site-form-gdpr" isn't seeded, e.g. c975l/site-bundle isn't installed
        if ($this->configService->get('site-form-gdpr') ?? true) {
            $builder->add('gdpr', CheckboxType::class, [
                'label' => 'text.gdpr',
                'translation_domain' => 'site',
                'required' => true,
                'mapped' => false,
                // 'required' alone is HTML5-only - this is what actually rejects an unchecked box server-side
                'constraints' => [
                    new IsTrue(message: 'gdpr.required'),
                ],
            ]);
        }
    }

    private function resolveFieldType(string $type): string
    {
        return match ($type) {
            FormField::TYPE_TEXTAREA => TextareaType::class,
            FormField::TYPE_EMAIL => EmailType::class,
            FormField::TYPE_CHECKBOX => CheckboxType::class,
            default => TextType::class,
        };
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'ui',
            'offerReceiveCopy' => false,
            'prefill' => [],
        ]);
        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', 'iterable');
        $resolver->setAllowedTypes('offerReceiveCopy', 'bool');
        $resolver->setAllowedTypes('prefill', 'array');
    }
}
