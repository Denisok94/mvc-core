<?php

namespace LiteMvc\Core;

class Html
{
    /**
     * @var string Regular expression used for attribute name validation.
     * @since 2.0.12
     */
    public static $attributeRegex = '/(^|.*\])([\w\.\+]+)(\[.*|$)/u';
    /**
     * @var array list of void elements (element name => 1)
     * @see http://www.w3.org/TR/html-markup/syntax.html#void-element
     */
    public static $voidElements = [
        'area' => 1,
        'base' => 1,
        'br' => 1,
        'col' => 1,
        'command' => 1,
        'embed' => 1,
        'hr' => 1,
        'img' => 1,
        'input' => 1,
        'keygen' => 1,
        'link' => 1,
        'meta' => 1,
        'param' => 1,
        'source' => 1,
        'track' => 1,
        'wbr' => 1,
    ];
    /**
     * @var array the preferred order of attributes in a tag. This mainly affects the order of the attributes
     * that are rendered by [[renderTagAttributes()]].
     */
    public static $attributeOrder = [
        'type',
        'id',
        'class',
        'name',
        'value',

        'href',
        'src',
        'srcset',
        'form',
        'action',
        'method',

        'selected',
        'checked',
        'readonly',
        'disabled',
        'multiple',

        'size',
        'maxlength',
        'width',
        'height',
        'rows',
        'cols',

        'alt',
        'title',
        'rel',
        'media',
    ];
    /**
     * @var array list of tag attributes that should be specially handled when their values are of array type.
     * In particular, if the value of the `data` attribute is `['name' => 'xyz', 'age' => 13]`, two attributes
     * will be generated instead of one: `data-name="xyz" data-age="13"`.
     * @since 2.0.3
     */
    public static $dataAttributes = ['aria', 'data', 'data-ng', 'ng'];

    /**
     * @var bool whether to removes duplicate class names in tag attribute `class`
     * @see mergeCssClasses()
     * @see renderTagAttributes()
     * @since 2.0.44
     */
    public static $normalizeClassAttribute = false;

    /**
     * Encodes special characters into HTML entities.
     * The [[\yii\base\Application::charset|application charset]] will be used for encoding.
     * @param string $content the content to be encoded
     * @param bool $doubleEncode whether to encode HTML entities in `$content`. If false,
     * HTML entities in `$content` will not be further encoded.
     * @return string the encoded content
     * @see decode()
     * @see https://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function encode($content, $doubleEncode = true)
    {
        return htmlspecialchars((string)$content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }


    /**
     * Generates a complete HTML tag.
     * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
     * @param string $content 
     * @param array $options
     * @return string the generated HTML tag
     */
    public static function tag($name, $content = '', $options = [])
    {
        if ($name === null || $name === false) {
            return $content;
        }
        $html = "<$name" . static::renderTagAttributes($options) . '>';
        return isset(static::$voidElements[strtolower($name)]) ? $html : "$html$content</$name>";
    }

    /**
     * Generates a start tag.
     * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string the generated start tag
     * @see endTag()
     * @see tag()
     */
    public static function beginTag($name, $options = [])
    {
        if ($name === null || $name === false) {
            return '';
        }
        return "<$name" . static::renderTagAttributes($options) . '>';
    }

    /**
     * Generates an end tag.
     * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
     * @return string the generated end tag
     * @see beginTag()
     * @see tag()
     */
    public static function endTag($name)
    {
        if ($name === null || $name === false) {
            return '';
        }
        return "</$name>";
    }

    /**
     * Generates a style tag.
     * @param string $content the style content
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string the generated style tag
     */
    public static function style($content, $options = [])
    {
        return static::tag('style', $content, $options);
    }

    /**
     * Generates a script tag.
     * @param string $content the script content
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string the generated script tag
     */
    public static function script($content, $options = [])
    {
        return static::tag('script', $content, $options);
    }

    /**
     * Generates a link tag that refers to an external CSS file.
     * @param array|string $url the URL of the external CSS file. This parameter will be processed by [[Url::to()]].
     * @param array $options 
     * @return string the generated link tag
     * @see Url::to()
     */
    public static function cssFile($url, $options = [])
    {
        if (!isset($options['rel'])) {
            $options['rel'] = 'stylesheet';
        }
        $options['href'] = $url;

        if (isset($options['noscript']) && $options['noscript'] === true) {
            unset($options['noscript']);
            return '<noscript>' . static::tag('link', '', $options) . '</noscript>';
        }

        return static::tag('link', '', $options);
    }

    /**
     * Generates a script tag that refers to an external JavaScript file.
     * @param string $url the URL of the external JavaScript file. This parameter will be processed by [[Url::to()]].
     * @param array $options 
     * @return string the generated script tag
     */
    public static function jsFile($url, $options = [])
    {
        $options['src'] = $url;
        return static::tag('script', '', $options);
    }

    /**
     * Renders the HTML tag attributes.
     *
     * Attributes whose values are of boolean type will be treated as
     * [boolean attributes](http://www.w3.org/TR/html5/infrastructure.html#boolean-attributes).
     *
     * Attributes whose values are null will not be rendered.
     *
     * The values of attributes will be HTML-encoded using [[encode()]].
     *
     * `aria` and `data` attributes get special handling when they are set to an array value. In these cases,
     * the array will be "expanded" and a list of ARIA/data attributes will be rendered. For example,
     * `'aria' => ['role' => 'checkbox', 'value' => 'true']` would be rendered as
     * `aria-role="checkbox" aria-value="true"`.
     *
     * If a nested `data` value is set to an array, it will be JSON-encoded. For example,
     * `'data' => ['params' => ['id' => 1, 'name' => 'yii']]` would be rendered as
     * `data-params='{"id":1,"name":"yii"}'`.
     *
     * @param array $attributes attributes to be rendered. The attribute values will be HTML-encoded using [[encode()]].
     * @return string the rendering result. If the attributes are not empty, they will be rendered
     * into a string with a leading white space (so that it can be directly appended to the tag name
     * in a tag). If there is no attribute, an empty string will be returned.
     * @see addCssClass()
     */
    public static function renderTagAttributes($attributes)
    {
        if (count($attributes) > 1) {
            $sorted = [];
            foreach (static::$attributeOrder as $name) {
                if (isset($attributes[$name])) {
                    $sorted[$name] = $attributes[$name];
                }
            }
            $attributes = array_merge($sorted, $attributes);
        }

        $html = '';
        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= " $name";
                }
            } elseif (is_array($value)) {
                if (in_array($name, static::$dataAttributes)) {
                    foreach ($value as $n => $v) {
                        if (is_array($v)) {
                            // $html .= " $name-$n='" . Json::htmlEncode($v) . "'";
                        } elseif (is_bool($v)) {
                            if ($v) {
                                $html .= " $name-$n";
                            }
                        } elseif ($v !== null) {
                            $html .= " $name-$n=\"" . static::encode($v) . '"';
                        }
                    }
                } elseif ($name === 'class') {
                    if (empty($value)) {
                        continue;
                    }
                    if (static::$normalizeClassAttribute === true && count($value) > 1) {
                        // removes duplicate classes
                        $value = explode(' ', implode(' ', $value));
                        $value = array_unique($value);
                    }
                    $html .= " $name=\"" . static::encode(implode(' ', $value)) . '"';
                } elseif ($name === 'style') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " $name=\"" . static::encode(static::cssStyleFromArray($value)) . '"';
                } else {
                    // $html .= " $name='" . Json::htmlEncode($value) . "'";
                }
            } elseif ($value !== null) {
                $html .= " $name=\"" . static::encode($value) . '"';
            }
        }

        return $html;
    }

    /**
     * Converts a CSS style array into a string representation.
     *
     * For example,
     *
     * ```php
     * print_r(Html::cssStyleFromArray(['width' => '100px', 'height' => '200px']));
     * // will display: 'width: 100px; height: 200px;'
     * ```
     *
     * @param array $style the CSS style array. The array keys are the CSS property names,
     * and the array values are the corresponding CSS property values.
     * @return string the CSS style string. If the CSS style is empty, a null will be returned.
     */
    public static function cssStyleFromArray(array $style)
    {
        $result = '';
        foreach ($style as $name => $value) {
            $result .= "$name: $value; ";
        }
        // return null if empty to avoid rendering the "style" attribute
        return $result === '' ? null : rtrim($result);
    }
}
