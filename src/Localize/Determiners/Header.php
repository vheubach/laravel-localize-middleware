<?php

namespace BenConstable\Localize\Determiners;

use Illuminate\Http\Request;

/**
 * This locale determiner fetches the locale from a request header.
 */
class Header extends Determiner
{
    /**
     * Name of the header that holds the locale.
     *
     * @var  string
     */
    private $header;

    /**
     * Constructor.
     *
     * @param  string  $header  Name of the header that holds the locale
     * @return  void
     */
    public function __construct($header)
    {
        $this->header = $header;
    }

    /**
     * Determine the locale from the request parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return  string
     */
    public function determineLocale(Request $request)
    {
        $header = $request->header($this->header, $this->fallback);

        $acceptLanguages = $this->sortBySignificance($header);

        return $this->getSupported(array_keys($acceptLanguages));
    }

    /**
     * Extract locales from header and sort them by significance.
     *
     * @param string $header
     *
     * @return array
     */
    public function sortBySignificance(string $header): array
    {
        $accepts = explode(',', $header);
        $acceptLanguages = [];

        foreach ($accepts as $accept) {
            $locale = preg_replace('/([^;]+);.*$/', '${1}', $accept);

            $significance = preg_replace('/^[^q]*q=([^\,]+)*$/', '${1}', $accept);

            // The quality value defaults to "q=1"
            $significance = is_numeric($significance) ? (float) $significance : 1.0;

            $acceptLanguages[$locale] = $significance;
        }

        // Place higher-value on top
        array_multisort($acceptLanguages, SORT_DESC, SORT_NATURAL);

        return $acceptLanguages;
    }

    /**
     * Pick up the first supported language.
     *
     * @param array $acceptedLanguages
     *
     * @return string
     */
    public function getSupported(array $acceptedLanguages): ?string
    {
        $supportedLocales = locales()->pluck('id');

        foreach ($acceptedLanguages as $accepted) {
            // Turn en-gb into en
            $accepted = mb_substr($accepted, 0, 2);

            foreach ($supportedLocales as $supported) {
                if ($accepted === $supported) {
                    return $supported;
                }
            }
        }

        return null;
    }
}
