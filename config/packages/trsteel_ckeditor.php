<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('trsteel_ckeditor', [
        'external_plugins' => [
            'base64image' => [
                'path' => 'bundles/trsteelckeditor/plugins/base64image',
            ],
        ],
        'transformers' => [
        ],
        'toolbar' => [
            'document',
            'clipboard',
            'editing',
            '/',
            'basicstyles',
            'paragraph',
            'links',
            '/',
            'insert',
            'styles',
            'tools',
        ],
        'toolbar_groups' => [
            'document' => [
                'Source',
                '-',
                'Save',
                '-',
                'Templates',
            ],
            'clipboard' => [
                'Cut',
                'Copy',
                'Paste',
                'PasteText',
                'PasteFromWord',
                '-',
                'Undo',
                'Redo',
            ],
            'editing' => [
                'Find',
                'Replace',
                '-',
                'SelectAll',
            ],
            'basicstyles' => [
                'Bold',
                'Italic',
                'Underline',
                'Strike',
                'Subscript',
                'Superscript',
                '-',
                'RemoveFormat',
            ],
            'paragraph' => [
                'NumberedList',
                'BulletedList',
                '-',
                'Outdent',
                'Indent',
                '-',
                'JustifyLeft',
                'JustifyCenter',
                'JustifyRight',
                'JustifyBlock',
            ],
            'links' => [
                'Link',
                'Unlink',
                'Anchor',
            ],
            'insert' => [
                'Image',
                'base64image',
                'Flash',
                'Table',
                'HorizontalRule',
            ],
            'styles' => [
                'Styles',
                'Format',
            ],
            'tools' => [
                'Maximize',
                'ShowBlocks',
            ],
        ],
        'enter_mode' => 'ENTER_BR',
    ]);
};
