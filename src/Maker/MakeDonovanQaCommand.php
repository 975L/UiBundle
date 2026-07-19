<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

// Generates a self-hosted backend for UiBundle's dashboard assistant ("Donovan (Q&A)", see Readme "AI
// Assistant" > "Self-hosting your own backend"): a controller, an LLM client, a block-context builder, and
// a setup-guide Twig extension + template override (matching 975l.com's own reference implementation,
// AiHelpController/AiHelpLlmClient/DonovanQaExtension, minus its multi-site answer caching - not needed
// for a first self-hosted backend). Doesn't touch config/configs.json: creating/loading app-level config
// entries is app-specific (see 975l.com's own 15-line AppConfigLoadCommand for a model), so the needed
// entries are printed as a snippet to paste instead - same reasoning as MakeBlockCommand's services.yaml
// snippet, never guess-merge into a file this maker doesn't own
class MakeDonovanQaCommand extends AbstractMaker
{
    private const SKELETON_DIR = __DIR__ . '/../Resources/skeleton/donovan_qa';

    public static function getCommandName(): string
    {
        return 'c975l:ui:donovan-qa:create';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates the skeleton of a self-hosted Donovan (Q&A) backend (controller, LLM client, context builder, setup guide)';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        // No argument needed: every generated class/slug is fixed ("Donovan" is hardcoded bundle-wide,
        // see AiRephraseExtension::assistantName()) - nothing here is meant to vary per app
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $llmClientClass = $generator->createClassNameDetails('DonovanQaLlm', 'Service\\', 'Client');
        $contextBuilderClass = $generator->createClassNameDetails('DonovanQaContext', 'Service\\', 'Builder');
        $controllerClass = $generator->createClassNameDetails('DonovanQa', 'Controller\\Api\\', 'Controller');
        $twigExtensionClass = $generator->createClassNameDetails('DonovanQa', 'Twig\\', 'Extension');
        $testClass = $generator->createClassNameDetails('DonovanQaLlm', 'Tests\\Service\\', 'ClientTest');

        $generator->generateClass($llmClientClass->getFullName(), self::SKELETON_DIR . '/LlmClient.tpl.php');

        $generator->generateClass($contextBuilderClass->getFullName(), self::SKELETON_DIR . '/ContextBuilder.tpl.php');

        $generator->generateClass($controllerClass->getFullName(), self::SKELETON_DIR . '/Controller.tpl.php', [
            'llm_client_full_name' => $llmClientClass->getFullName(),
            'llm_client_short_name' => $llmClientClass->getShortName(),
            'context_builder_full_name' => $contextBuilderClass->getFullName(),
            'context_builder_short_name' => $contextBuilderClass->getShortName(),
        ]);

        $generator->generateClass($twigExtensionClass->getFullName(), self::SKELETON_DIR . '/TwigExtension.tpl.php', [
            'llm_client_full_name' => $llmClientClass->getFullName(),
            'llm_client_short_name' => $llmClientClass->getShortName(),
        ]);

        $generator->generateClass($testClass->getFullName(), self::SKELETON_DIR . '/LlmClientTest.tpl.php', [
            'llm_client_full_name' => $llmClientClass->getFullName(),
            'llm_client_short_name' => $llmClientClass->getShortName(),
        ]);

        // Fixed physical path: this is how Symfony resolves "@c975LUi/management/ai_assistant.html.twig"
        // once it exists (see that path's own comment for why it must extend _ai_assistant_base.html.twig,
        // never itself)
        $generator->generateTemplate(
            'bundles/c975LUiBundle/management/ai_assistant.html.twig',
            self::SKELETON_DIR . '/ai_assistant_override.tpl.php',
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->writeln([
            'Still to do:',
            '',
            '1. Add these 6 entries to your app\'s config/configs.json (create it, plus a small command',
            '   loading it via ConfigServiceInterface::loadDefaultConfig() at boot/deploy time, if you don\'t',
            '   have one yet - see 975l.com\'s own AppConfigLoadCommand for a 15-line model):',
            '',
            '    [',
            '        {',
            '            "label": "label.donovan_qa_llm_enabled",',
            '            "slug": "donovan-qa-llm-enabled",',
            '            "sensitive": false,',
            '            "restricted": false,',
            '            "value": "false",',
            '            "kind": "bool",',
            '            "group": "ai",',
            '            "severity": null,',
            '            "description": "description.donovan_qa_llm_enabled"',
            '        },',
            '        {',
            '            "label": "label.donovan_qa_llm_provider",',
            '            "slug": "donovan-qa-llm-provider",',
            '            "sensitive": false,',
            '            "restricted": false,',
            '            "value": "anthropic",',
            '            "kind": "text",',
            '            "group": "ai",',
            '            "severity": null,',
            '            "description": "description.donovan_qa_llm_provider"',
            '        },',
            '        {',
            '            "label": "label.donovan_qa_llm_api_key",',
            '            "slug": "donovan-qa-llm-api-key",',
            '            "sensitive": true,',
            '            "restricted": false,',
            '            "value": null,',
            '            "kind": "text",',
            '            "group": "ai",',
            '            "severity": null,',
            '            "description": "description.donovan_qa_llm_api_key"',
            '        },',
            '        {',
            '            "label": "label.donovan_qa_llm_model",',
            '            "slug": "donovan-qa-llm-model",',
            '            "sensitive": false,',
            '            "restricted": false,',
            '            "value": null,',
            '            "kind": "text",',
            '            "group": "ai",',
            '            "severity": null,',
            '            "description": "description.donovan_qa_llm_model"',
            '        },',
            '        {',
            '            "label": "label.donovan_qa_llm_base_uri",',
            '            "slug": "donovan-qa-llm-base-uri",',
            '            "sensitive": false,',
            '            "restricted": false,',
            '            "value": null,',
            '            "kind": "text",',
            '            "group": "ai",',
            '            "severity": null,',
            '            "description": "description.donovan_qa_llm_base_uri"',
            '        },',
            '        {',
            '            "label": "label.donovan_qa_authorized_tokens",',
            '            "slug": "donovan-qa-authorized-tokens",',
            '            "sensitive": true,',
            '            "restricted": false,',
            '            "value": null,',
            '            "kind": "json",',
            '            "group": "ai",',
            '            "severity": null,',
            '            "description": "description.donovan_qa_authorized_tokens"',
            '        }',
            '    ]',
            '',
            '2. Point every consuming site\'s "ui-ai-assistant-dashboard-endpoint" at this app\'s',
            '   /api/donovan-qa/ask, and its "ui-ai-assistant-dashboard-token" at its own token above.',
            '',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
