<?php

namespace AmphiBee\AcfBlocks;

use WordPlate\Acf\Location;

class Block
{

    protected $name;
    protected $title;
    protected $description;
    protected $category = '';
    protected $icon = '';
    protected $keywords = [];
    protected $fields;
    protected $renderTemplate;
    protected $loadAllField = false;
    protected $postTypes = [];
    protected $mode = 'preview';
    protected $align = '';
    protected $alignText;
    protected $alignContent = 'top';
    protected $enqueueScript;
    protected $enqueueStyle;
    protected $supports = [];
    protected $example = [];
    protected $jsxTemplate = [];
    protected $allowedBlocks = [];
    protected $templateLock;

    public function __construct(string $title, ?string $name = null)
    {
        $this->setName($name ?? str_replace('\\', '-', strtolower(get_class($this))) . '-' . acf_slugify($title));
        $this->setTitle($title);

        add_action('acf/init', [$this, 'registerBlock']);
        add_action('acf/init', [$this, 'registerFieldGroup']);
    }

    /**
     * Instantiate a new block
     * @param string $title The display title for your block
     * @param string|null $name A unique name that identifies the block (without namespace)
     * @return static
     */
    public static function make(string $title, string $name = null): self
    {
        return new static($title, $name);
    }

    /**
     * Register ACF Gutenberg Block
     * @return void
     */
    public function registerBlock()
    {
        acf_register_block_type([
            'title' => $this->getTitle(),
            'name' => $this->getName(),
            'category' => $this->getCategory(),
            'icon' => $this->getIcon(),
            'post_types' => $this->getPostTypes(),
            'mode' => $this->getMode(),
            'align' => $this->getAlign(),
            'align_text' => $this->getAlignText(),
            'align_content' => $this->getAlignContent(),
            'enqueue_script' => $this->getEnqueueScript(),
            'enqueue_style' => $this->getEnqueueStyle(),
            'supports' => $this->getSupports(),
            'keywords' => $this->getKeywords(),
            'render_callback' => function ($block) {

                $viewArgs = [
                    'block' => $block,
                    'instance' => $this,
                ];

                $viewArgs['innerBlocks'] = '';

                if ($this->getSupport('jsx')) {
                    $innerBlockAttrs = '';
                    if (count($this->jsxTemplate) > 0) {
                        $viewArgs['template_attr'] = ' template="' . esc_attr(wp_json_encode($this->jsxTemplate)) . '"';
                        $innerBlockAttrs .= $viewArgs['template_attr'];
                    }

                    if (count($this->allowedBlocks) > 0) {
                        $viewArgs['allowed_block_attr'] = ' allowedBlocks="' . esc_attr(wp_json_encode($this->allowedBlocks)) . '"';
                        $innerBlockAttrs .= $viewArgs['allowed_block_attr'];
                    }

                    if ($this->templateLock) {
                        $viewArgs['template_lock_attr'] = ' templateLock="' . $this->templateLock . '"';
                        $innerBlockAttrs .= $viewArgs['template_lock_attr'];
                    }
                    $viewArgs['innerBlocks'] = "<InnerBlocks{$innerBlockAttrs} />";
                }

                if ($this->loadAllField) {
                    $viewArgs['field'] = (object)get_fields();
                }

                $this->render($this->renderTemplate, $viewArgs);
            },
        ]);
    }

    /**
     * Render the template
     * @param string $tpl Template file or view path
     * @param array $args View arguments
     * @return void
     */
    public function render(string $tpl = '', array $args = [])
    {
        $tpl = str_replace(['.blade.php'], '', $tpl);

        if (function_exists('view') && view()->exists($tpl)) {
            echo view($tpl, $args);
            return;
        }
        if (function_exists('\Roots\view') && \Roots\view()->exists($tpl)) {
            echo \Roots\view($tpl, $args);
            return;
        }

        $locatedTemplate = locate_template($tpl, false, false, $args);

        if ($locatedTemplate) {
            extract($args);
            include($locatedTemplate);
        }
    }

    /**
     * Register the field group if fields are defined
     * @return void
     */
    public function registerFieldGroup()
    {
        if ($this->fields) {
            $name = acf_slugify($this->name);
            register_extended_field_group([
                'title' => $this->getTitle(),
                'fields' => $this->getFields(),
                'location' => [Location::if('block', 'acf/' . $name)],
            ]);
        }
    }

    /**
     * Instantiate all ACF field values inside the bloc view
     * @return $this
     */
    public function loadAllFields(): self
    {
        $this->loadAllField = true;
        return $this;
    }


    /**
     * Name of the block
     * @param string $name A unique name that identifies the block (without namespace
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the block
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the block title
     * @param string $title The display title for your block
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the block title
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the block description
     * @param string $description The description for your block
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the block description
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the block category
     * @param string $category The category for your block
     * @return $this
     */
    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Get the block category
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Get the block icon
     * @return string The icon for your block
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Set the block icon
     * @param string $icon
     * @return $this
     */
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Set the block keywords
     * @param array $keywords An array of search terms to help user discover the block while searching.
     * @return $this
     */
    public function setKeywords(array $keywords): self
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * Get the block keywords
     * @return array
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
     * Restricted Post Types
     * @param array $postTypes An array of post types to restrict this block type to.
     * @return $this
     */
    public function setPostTypes(array $postTypes): self
    {
        $this->postTypes = $postTypes;
        return $this;
    }

    /**
     * Return the restricted post types
     * @return array
     */
    public function getPostTypes(): array
    {
        return $this->postTypes;
    }

    /**
     * Display mode
     * @param string $mode The display mode for your block. Available settings are "auto", "preview" and "edit". Defaults to "preview"
     * @return $this
     */
    public function setMode(string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get current display mode
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Default block alignment
     * @param string $align The default block alignment. Available settings are "left", "center", "right", "wide" and "full". Defaults to an empty string.
     * @return $this
     */
    public function setAlign(string $align): self
    {
        $this->align = $align;
        return $this;
    }

    /**
     * Get the default block alignment
     * @return string
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * Default block text alignment
     * @param string $alignText The default block text alignment (see supports setting for more info). Available settings are "left", "center" and "right". Defaults to the current language’s text alignment.
     * @return $this
     */
    public function setAlignText(string $alignText): self
    {
        $this->alignText = $alignText;
        return $this;
    }

    /**
     * Get the default block text alignment
     * @return mixed
     */
    public function getAlignText()
    {
        return $this->alignText;
    }

    /**
     * Default block content alignment
     * @param string $alignContent The default block content alignment (see supports setting for more info). Available settings are "top", "center" and "bottom". When utilising the "Matrix" control type, additional settings are available to specify all 9 positions from "top left" to "bottom right".
     * @return $this
     */
    public function setAlignContent(string $alignContent): self
    {
        $this->alignContent = $alignContent;
        return $this;
    }

    /**
     * Get the default block content alignment
     * @return string
     */
    public function getAlignContent(): string
    {
        return $this->alignContent;
    }

    /**
     * Set the script to enqueue
     * @param string $enqueueScript The url to a .js file to be enqueued whenever your block is displayed (front-end and back-end).
     * @return $this
     */
    public function setEnqueueScript(string $enqueueScript): self
    {
        $this->enqueueScript = $enqueueScript;
        return $this;
    }

    /**
     * Get the enqueued script
     * @return mixed
     */
    public function getEnqueueScript()
    {
        return $this->enqueueScript;
    }

    /**
     * Set the style to enqueue
     * @param string $enqueueStyle The url to a .css file to be enqueued whenever your block is displayed (front-end and back-end).
     * @return $this
     */
    public function setEnqueueStyle(string $enqueueStyle): self
    {
        $this->enqueueStyle = $enqueueStyle;
        return $this;
    }

    /**
     * Get the enqueued style
     * @return mixed
     */
    public function getEnqueueStyle()
    {
        return $this->enqueueStyle;
    }

    /**
     * Set the render template
     * @param string $renderTemplate Path to the render template
     * @return $this
     */
    public function setRenderTemplate(string $renderTemplate): self
    {
        $this->renderTemplate = $renderTemplate;
        return $this;
    }

    /**
     * Shortcut for setRenderTemplate (More Blade friendly)
     * @param string $renderTemplate Path to the render template (blade path)
     * @return $this
     */
    public function setView(string $view): self
    {
        $this->setRenderTemplate($view);
        return $this;
    }

    /**
     * Get the render template
     * @return string
     */
    public function getRenderTemplate(): string
    {
        return $this->renderTemplate;
    }

    /**
     * Shortcut for getRenderTemplate (More Blade friendly)
     * @return string
     */
    public function getView(): string
    {
        return $this->getRenderTemplate();
    }

    /**
     * @param array $supports An array of features to support.
     * All properties from the JavaScript block supports
     * documentation may be used.
     * See https://www.advancedcustomfields.com/resources/acf_register_block_type/
     * @return $this
     */
    public function setSupports(array $supports): self
    {
        $this->supports = $supports;
        return $this;
    }

    /**
     * Get the current support values
     * @return array
     */
    public function getSupports(): array
    {
        return $this->supports;
    }

    /**
     * This disables the toolbar button to control the block’s
     * alignment.
     * @return $this
     */
    public function disableAlign(): self
    {
        $this->supports['align'] = false;
        return $this;
    }

    /**
     * Customize alignment toolbar
     * @param array $alignSupport
     * @return $this
     */
    public function setAlignSupport(array $alignSupport): self
    {
        $this->supports['align'] = $alignSupport;
        return $this;
    }

    /**
     * This property enables a toolbar button to control the block's
     * text alignment
     * @return $this
     */
    public function enableAlignText(): self
    {
        $this->supports['align_text'] = true;
        return $this;
    }

    /**
     * This method enables a toolbar button to control the block's
     * inner content alignment
     * @return $this
     */
    public function enableAlignContent(): self
    {
        $this->supports['align_content'] = true;
        return $this;
    }

    /**
     * This method control the block's inner content alignment.
     * Set to true to show the alignment toolbar button, or set
     * to 'matrix' to enable the full alignment matrix in the toolbar
     * @param mixed $setting
     * @return $this
     */
    public function setAlignContentSupport($setting): self
    {
        $this->supports['align_content'] = $setting;
        return $this;
    }

    /**
     * This method enables the full height button on the toolbar of a
     * block and adds the $block[‘full_height’] property inside the
     * render template/callback. $block[‘full_height’] will only be
     * true if the full height button is enabled on the block in
     * the editor
     * @return $this
     */
    public function enableFullHeight(): self
    {
        $this->supports['full_height'] = true;
        return $this;
    }

    /**
     * This method disable the toggle between edit and preview modes
     * @return $this
     */
    public function disableMode(): self
    {
        $this->supports['mode'] = false;
        return $this;
    }

    /**
     * Disable the block custom class names
     * @return $this
     */
    public function disableCustomClasseName(): self
    {
        $this->supports['customClassName'] = false;
        return $this;
    }

    /**
     * Enable Anchor
     * @return $this
     */
    public function enableAnchor(): self
    {
        $this->supports['anchor'] = true;
        return $this;
    }

    /**
     * Enable JSX support
     * @return $this
     */
    public function enableJsx(): self
    {
        $this->supports['jsx'] = true;
        return $this;
    }

    /**
     * Set the InnerBlock template
     * @param array $template Array of blocks. See https://developer.wordpress.org/block-editor/reference-guides/block-api/block-templates/
     * @return $this
     */
    public function setJsxTemplate(array $template): self
    {
        $this->jsxTemplate = $template;
        return $this;
    }

    /**
     * Set the InnerBlock allowed blocks
     * @param array $allowedBlocks Array of blocks
     * @return $this
     */
    public function setAllowedBlocks(array $allowedBlocks): self
    {
        $this->allowedBlocks = $allowedBlocks;
        return $this;
    }

    /**
     * Set the InnerBlock template lock settings
     * @param array $templateLock InnerBlock template lock settings (possible value : all|insert|move|delete)
     * @return $this
     */
    public function setTemplateLock($templateLock): self
    {
        $this->templateLock = $templateLock;
        return $this;
    }

    /**
     * Add specific support
     * @param string $key Support key
     * @param mixed $value Support value
     * @return $this
     */
    public function addSupport(string $key, $value): self
    {
        $this->supports[$key] = $value;
        return $this;
    }

    /**
     * Get specific support value
     * @param string $key Support value
     * @return mixed
     */
    public function getSupport(string $key)
    {
        return $this->supports[$key];
    }

    /**
     * An array of structured data used to construct a preview shown
     * within the block-inserter. All values entered into the ‘data’
     * attribute array will become available within the block render
     * template/callback via $block['data'] or get_field().
     * @param array $example
     * @return $this
     */
    public function setExample(array $example): self
    {
        $this->example = $example;
        return $this;
    }

    public function getExample(): array
    {
        return $this->example;
    }

    /**
     * Set the block fields
     * @param array $fields Array of fields declared via
     * WordPlate Extended ACF library
     * @return $this
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Get the block fields
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

}