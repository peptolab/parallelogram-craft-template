<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Volume;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;

class m260302_000001_create_page_structure extends Migration
{
    public function safeUp(): bool
    {
        // --- Filesystem & Volume ---

        if (!Craft::$app->getFs()->getFilesystemByHandle('local')) {
            $fs = new \craft\fs\Local([
                'name' => 'Local',
                'handle' => 'local',
                'hasUrls' => true,
                'url' => '@web/uploads',
                'path' => '@webroot/uploads',
            ]);
            if (!Craft::$app->getFs()->saveFilesystem($fs)) {
                throw new \RuntimeException('Could not save filesystem');
            }
        }

        if (!Craft::$app->getVolumes()->getVolumeByHandle('media')) {
            $volume = new Volume();
            $volume->name = 'Media';
            $volume->handle = 'media';
            $volume->setFsHandle('local');
            if (!Craft::$app->getVolumes()->saveVolume($volume)) {
                throw new \RuntimeException('Could not save volume');
            }
        }

        // --- Fields ---

        $heading = $this->_field(\craft\fields\PlainText::class, 'Heading', 'heading', [
            'multiline' => true,
            'initialRows' => 1,
        ]);

        $description = $this->_field(\craft\ckeditor\Field::class, 'Description', 'description', [
            'ckeConfig' => $this->_findCkeConfig('Advanced') ?? $this->_findCkeConfig('Simple'),
        ]);

        $image = $this->_field(\craft\fields\Assets::class, 'Image', 'image', [
            'restrictFiles' => true,
            'allowedKinds' => ['image'],
            'sources' => '*',
            'maxRelations' => 1,
        ]);

        $caption = $this->_field(\craft\ckeditor\Field::class, 'Caption', 'caption', [
            'ckeConfig' => $this->_findCkeConfig('Simple'),
        ]);

        $imageAlignment = $this->_field(\craft\fields\Dropdown::class, 'Image Alignment', 'imageAlignment', [
            'options' => [
                ['label' => 'Left', 'value' => 'left', 'default' => true],
                ['label' => 'Right', 'value' => 'right', 'default' => false],
            ],
        ]);

        $callToAction = $this->_field(\craft\fields\PlainText::class, 'Call to Action', 'callToAction', [
            'multiline' => true,
            'initialRows' => 1,
        ]);

        $linkedAction = $this->_field(\craft\fields\Link::class, 'Linked Action', 'linkedAction', [
            'types' => ['url', 'entry', 'email', 'tel'],
            'showLabelField' => true,
        ]);

        $theme = $this->_field(\craft\fields\Dropdown::class, 'Theme', 'theme', [
            'options' => [
                ['label' => 'Default', 'value' => 'default', 'default' => true],
                ['label' => 'Light', 'value' => 'light', 'default' => false],
                ['label' => 'Dark', 'value' => 'dark', 'default' => false],
                ['label' => 'Black', 'value' => 'black', 'default' => false],
            ],
        ]);

        $size = $this->_field(\craft\fields\Dropdown::class, 'Size', 'size', [
            'options' => [
                ['label' => 'Regular', 'value' => 'regular', 'default' => true],
                ['label' => 'Small', 'value' => 'sm', 'default' => false],
                ['label' => 'Large', 'value' => 'lg', 'default' => false],
                ['label' => 'XLarge', 'value' => 'xl', 'default' => false],
            ],
        ]);

        $layout = $this->_field(\craft\fields\Dropdown::class, 'Layout', 'layout', [
            'options' => [
                ['label' => 'Regular', 'value' => 'regular', 'default' => true],
            ],
        ]);

        $common = [$callToAction, $linkedAction, $theme, $size, $layout];

        // --- Nested Entry Types ---

        $accordionItemType = $this->_entryType('Accordion Item', 'accordionItem',
            [$description], true, '{title}');

        $accordionItems = $this->_matrixField('Accordion Items', 'accordionItems', [$accordionItemType]);

        $slideType = $this->_entryType('Slide', 'slide',
            [$image, $caption, $linkedAction], false, 'Slide');

        $slides = $this->_matrixField('Slides', 'slides', [$slideType]);

        // Formie form field (optional)
        $formField = null;
        if (Craft::$app->plugins->isPluginInstalled('formie')) {
            $formField = $this->_field(\verbb\formie\fields\Forms::class, 'Form', 'form', [
                'sources' => '*',
            ]);
        }

        // --- Block Entry Types ---

        $textType = $this->_entryType('Text', 'text',
            array_merge([$heading, $description], $common), false, '{heading}');

        $imageBlockType = $this->_entryType('Image', 'imageBlock',
            array_merge([$image, $caption], $common), false, 'Image');

        $textImageType = $this->_entryType('Text + Image', 'textImage',
            array_merge([$heading, $description, $image, $imageAlignment], $common), false, '{heading}');

        $carouselType = $this->_entryType('Carousel', 'carousel',
            array_merge([$heading, $slides], $common), false, '{heading}');

        $accordionsType = $this->_entryType('Accordions', 'accordions',
            array_merge([$heading, $accordionItems], $common), false, '{heading}');

        $formBlockFields = [$heading, $description];
        if ($formField) {
            $formBlockFields[] = $formField;
        }
        $formBlockType = $this->_entryType('Form', 'formBlock',
            array_merge($formBlockFields, $common), false, '{heading}');

        // --- Content Block Matrix ---

        $contentBlock = $this->_matrixField('Content Block', 'contentBlock', [
            $textType, $imageBlockType, $textImageType,
            $carouselType, $accordionsType, $formBlockType,
        ]);

        // --- SEO Field ---

        $seoField = null;
        if (Craft::$app->plugins->isPluginInstalled('seomatic')) {
            $seoField = $this->_field(\nystudio107\seomatic\fields\SeoSettings::class, 'SEO', 'seo');
        }

        // --- Page Entry Type (multi-tab layout) ---

        $pageLayout = new FieldLayout();

        $contentTab = new FieldLayoutTab();
        $contentTab->name = 'Content';
        $contentTab->setLayout($pageLayout);
        $contentTab->setElements([
            new EntryTitleField(),
            $this->_customField($image),
            $this->_customField($contentBlock),
        ]);

        $tabs = [$contentTab];

        if ($seoField) {
            $seoTab = new FieldLayoutTab();
            $seoTab->name = 'SEO';
            $seoTab->setLayout($pageLayout);
            $seoTab->setElements([$this->_customField($seoField)]);
            $tabs[] = $seoTab;
        }

        $pageLayout->setTabs($tabs);

        $pageType = new EntryType();
        $pageType->name = 'Page';
        $pageType->handle = 'page';
        $pageType->hasTitleField = true;
        $pageType->setFieldLayout($pageLayout);

        if (!Craft::$app->getEntries()->saveEntryType($pageType)) {
            throw new \RuntimeException('Could not save Page entry type');
        }

        // --- Page Section ---

        $section = new Section();
        $section->name = 'Page';
        $section->handle = 'page';
        $section->type = Section::TYPE_STRUCTURE;
        $section->enableVersioning = true;

        $siteSettings = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $ss = new Section_SiteSettings();
            $ss->siteId = $site->id;
            $ss->enabledByDefault = true;
            $ss->hasUrls = true;
            $ss->uriFormat = '{parent.uri}/{slug}';
            $ss->template = 'page/_entry';
            $siteSettings[$site->id] = $ss;
        }
        $section->setSiteSettings($siteSettings);

        $section->setEntryTypes([$pageType]);

        if (!Craft::$app->getEntries()->saveSection($section)) {
            $errors = [];
            foreach ($section->getErrors() as $attr => $errs) {
                $errors[] = "$attr: " . implode(', ', $errs);
            }
            foreach ($section->getSiteSettings() as $ss) {
                foreach ($ss->getErrors() as $attr => $errs) {
                    $errors[] = "siteSettings.$attr: " . implode(', ', $errs);
                }
            }
            throw new \RuntimeException('Could not save Page section: ' . implode('; ', $errors));
        }

        // --- Homepage Single ---

        $homepage = new Section();
        $homepage->name = 'Homepage';
        $homepage->handle = 'homepage';
        $homepage->type = Section::TYPE_SINGLE;
        $homepage->enableVersioning = true;

        $homeSiteSettings = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $ss = new Section_SiteSettings();
            $ss->siteId = $site->id;
            $ss->enabledByDefault = true;
            $ss->hasUrls = true;
            $ss->uriFormat = '__home__';
            $ss->template = 'homepage/_entry';
            $homeSiteSettings[$site->id] = $ss;
        }
        $homepage->setSiteSettings($homeSiteSettings);
        $homepage->setEntryTypes([$pageType]);

        if (!Craft::$app->getEntries()->saveSection($homepage)) {
            throw new \RuntimeException('Could not save Homepage section');
        }

        return true;
    }

    public function safeDown(): bool
    {
        $homepage = Craft::$app->getEntries()->getSectionByHandle('homepage');
        if ($homepage) {
            Craft::$app->getEntries()->deleteSection($homepage);
        }

        $section = Craft::$app->getEntries()->getSectionByHandle('page');
        if ($section) {
            Craft::$app->getEntries()->deleteSection($section);
        }

        $fieldHandles = [
            'contentBlock', 'accordionItems', 'slides', 'seo', 'form',
            'heading', 'description', 'image', 'caption', 'imageAlignment',
            'callToAction', 'linkedAction', 'theme', 'size', 'layout',
        ];
        foreach ($fieldHandles as $handle) {
            $field = Craft::$app->getFields()->getFieldByHandle($handle);
            if ($field) {
                Craft::$app->getFields()->deleteField($field);
            }
        }

        $typeHandles = [
            'page', 'text', 'imageBlock', 'textImage', 'carousel',
            'accordions', 'formBlock', 'accordionItem', 'slide',
        ];
        foreach ($typeHandles as $handle) {
            $type = Craft::$app->getEntries()->getEntryTypeByHandle($handle);
            if ($type) {
                Craft::$app->getEntries()->deleteEntryType($type);
            }
        }

        $volume = Craft::$app->getVolumes()->getVolumeByHandle('media');
        if ($volume) {
            Craft::$app->getVolumes()->deleteVolume($volume);
        }

        $fs = Craft::$app->getFs()->getFilesystemByHandle('local');
        if ($fs) {
            Craft::$app->getFs()->removeFilesystem($fs);
        }

        return true;
    }

    // --- Helpers ---

    private function _field(string $class, string $name, string $handle, array $config = []): \craft\base\FieldInterface
    {
        $field = new $class();
        $field->name = $name;
        $field->handle = $handle;
        foreach ($config as $k => $v) {
            $field->$k = $v;
        }
        if (!Craft::$app->getFields()->saveField($field)) {
            throw new \RuntimeException(
                "Could not save field '$handle': " . implode(', ', $field->getFirstErrors())
            );
        }
        return $field;
    }

    private function _entryType(
        string $name,
        string $handle,
        array $fields,
        bool $withTitle = false,
        ?string $titleFormat = null
    ): EntryType {
        $elements = [];
        if ($withTitle) {
            $elements[] = new EntryTitleField();
        }
        foreach ($fields as $field) {
            $elements[] = $this->_customField($field);
        }

        $fl = new FieldLayout();
        $tab = new FieldLayoutTab();
        $tab->name = 'Content';
        $tab->setLayout($fl);
        $tab->setElements($elements);
        $fl->setTabs([$tab]);

        $type = new EntryType();
        $type->name = $name;
        $type->handle = $handle;
        $type->hasTitleField = $withTitle;
        if (!$withTitle && $titleFormat !== null) {
            $type->titleFormat = $titleFormat;
        }
        $type->setFieldLayout($fl);

        if (!Craft::$app->getEntries()->saveEntryType($type)) {
            throw new \RuntimeException("Could not save entry type '$handle'");
        }

        return $type;
    }

    private function _matrixField(string $name, string $handle, array $entryTypes): \craft\fields\Matrix
    {
        $field = new \craft\fields\Matrix();
        $field->name = $name;
        $field->handle = $handle;
        $field->setEntryTypes($entryTypes);
        if (!Craft::$app->getFields()->saveField($field)) {
            throw new \RuntimeException(
                "Could not save matrix field '$handle': " . implode(', ', $field->getFirstErrors())
            );
        }
        return $field;
    }

    private function _customField(\craft\base\FieldInterface $field): CustomField
    {
        $el = new CustomField();
        $el->fieldUid = $field->uid;
        return $el;
    }

    private function _findCkeConfig(string $name): ?string
    {
        $plugin = Craft::$app->plugins->getPlugin('ckeditor');
        if (!$plugin) {
            return null;
        }

        try {
            foreach ($plugin->getCkeConfigs()->getAll() as $config) {
                if ($config->name === $name) {
                    return $config->uid;
                }
            }
        } catch (\Throwable) {
            // CKEditor API may differ
        }

        return null;
    }
}
