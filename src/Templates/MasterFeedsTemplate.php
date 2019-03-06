<?php

namespace RebelCode\Wpra\Core\Templates;

use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Output\CreateTemplateRenderExceptionCapableTrait;
use Dhii\Output\TemplateInterface;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use RebelCode\Wpra\Core\Data\DataSetInterface;
use WP_Post;

/**
 * An implementation of a standard Dhii template that, depending on context, delegates rendering to a WP RSS
 * Aggregator feeds template.
 *
 * @since [*next-version*]
 */
class MasterFeedsTemplate implements TemplateInterface
{
    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateTemplateRenderExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The ID of the template to use by default.
     *
     * @since [*next-version*]
     *
     * @var string
     */
    protected $default;

    /**
     * An associative array of template type instances.
     *
     * @since [*next-version*]
     *
     * @var FeedTemplateTypeInterface[]
     */
    protected $types;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string $default The name of the template to use by default.
     */
    public function __construct($default)
    {
        $this->types = [];
        $this->default = $default;
    }

    /**
     * Registers a new feed template type.
     *
     * @since [*next-version*]
     *
     * @param FeedTemplateTypeInterface $templateType The feed template type instance.
     */
    public function addTemplateType(FeedTemplateTypeInterface $templateType)
    {
        $this->types[$templateType->getKey()] = $templateType;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function render($ctx = null)
    {
        $arrCtx = $this->_normalizeArray($ctx);

        $templateKey = array_key_exists('template', $arrCtx)
            ? $arrCtx['template']
            : $this->default;

        $model = $this->getTemplateModel($templateKey);
        $type = $model['template_type'];
        $template = $this->getTemplateType($type);

        if ($template === null) {
            throw $this->_createTemplateRenderException(
                sprintf(__('Template "%s" does not exist', WPRSS_TEXT_DOMAIN), $templateKey),
                null, null, $this, $ctx
            );
        }

        return $template->render($model);
    }

    /**
     * Retrieves a template model by key.
     *
     * @since [*next-version*]
     *
     * @param string $key The key of the template.
     *
     * @return DataSetInterface|null The template model instance or null if not found.
     */
    public function getTemplateModel($key)
    {
        $posts = get_posts([
            'post_type' => 'wprss_feed_template',
            'posts_per_page' => 1,
            'name' => $key,
        ]);

        if (empty($posts)) {
            return null;
        }

        return $this->getTemplateModelForPost($posts[0]);
    }

    /**
     * Retrieves the template model for a WP RSS Aggregator feed template post.
     *
     * @since [*next-version*]
     *
     * @param WP_Post $post The post for the feed template.
     *
     * @return DataSetInterface The template model instance.
     */
    public function getTemplateModelForPost(WP_Post $post)
    {
        $templateType = $post->wprss_template_type;

        return ($templateType === '__built_in')
            ? new BuiltInFeedTemplate($post)
            : new WpPostFeedTemplate($post);
    }

    /**
     * Retrieves a template type by key.
     *
     * @since [*next-version*]
     *
     * @param $key
     *
     * @return mixed|FeedTemplateTypeInterface|string
     */
    protected function getTemplateType($key)
    {
        return isset($this->types[$key])
            ? $this->types[$key]
            : $this->types['list'];
    }
}