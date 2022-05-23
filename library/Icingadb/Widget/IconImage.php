<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class IconImage extends BaseHtmlElement
{
    /** @var string */
    protected $source;

    /** @var ?string */
    protected $alt;

    protected $tag = 'img';

    /**
     * Create a new icon image
     *
     * @param string $source
     * @param ?string $alt The alternative text
     */
    public function __construct(string $source, ?string $alt)
    {
        $this->source = $source;
        $this->alt = $alt;
    }

    public function renderUnwrapped()
    {
        if (! $this->getAttributes()->has('src')) {
            // If it's an icon we don't need the <img> tag
            return '';
        }

        return parent::renderUnwrapped();
    }

    protected function assemble()
    {
        $src = $this->source;

        if (strpos($src, '.') === false) {
            $this->setWrapper((new HtmlDocument())->addHtml(new Icon($src)));
            return;
        }

        if (strpos($src, '/') === false) {
            $src = 'img/icons/' . $src;
        }

        if (getenv('ICINGAWEB_EXPORT_FORMAT') === 'pdf') {
            $srcUrl = Url::fromPath($src);
            $srcPath = $srcUrl->getRelativeUrl();
            if (! $srcUrl->isExternal() && file_exists($srcPath) && is_file($srcPath)) {
                $mimeType = @mime_content_type($srcPath);
                $content = @file_get_contents($srcPath);
                if ($mimeType !== false && $content !== false) {
                    $src = "data:$mimeType;base64," . base64_encode($content);
                }
            }
        }

        $this->addAttributes([
            'src' => $src,
            'alt' => $this->alt
        ]);
    }
}
