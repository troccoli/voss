<?php

namespace Database\Factories;

trait WithLocales
{
    /** @var array<string, string> */
    private array $countries = [
        'ARG' => 'Argentina', 'BEL' => 'Belgium', 'BRA' => 'Brazil', 'CAN' => 'Canada',
        'HRV' => 'Croatia', 'CYP' => 'Cyprus', 'CZE' => 'Czech Republic', 'DNK' => 'Denmark',
        'EST' => 'Estonia', 'FIN' => 'Finland', 'FRA' => 'France', 'DEU' => 'Germany',
        'HUN' => 'Hungary', 'ISL' => 'Iceland', 'IND' => 'India', 'ITA' => 'Italy',
        'LVA' => 'Latvia', 'LTU' => 'Lithuania', 'NLD' => 'Netherlands', 'NOR' => 'Norway',
        'POL' => 'Poland', 'PRT' => 'Portugal', 'SVN' => 'Slovenia', 'ZAF' => 'South Africa',
        'ESP' => 'Spain', 'SWE' => 'Sweden', 'CHE' => 'Switzerland', 'GBR' => 'United Kingdom',
        'USA' => 'United States',
    ];

    private function randomCountryCode(): string
    {
        return $this->faker->unique()->randomElement(array_keys($this->countries));
    }

    private function getLocaleForCountry(string $countryCode): string
    {
        $locales = [
            'ARG' => 'es_AR', 'ARM' => 'hy_AM', 'AUT' => 'de_AT', 'BGD' => 'bn_BD',
            'BEL' => 'nl_BE', 'BRA' => 'pt_BR', 'BGR' => 'bg_BG', 'CAN' => 'en_CA',
            'CHN' => 'zh_CN', 'HRV' => 'hr_HR', 'CYP' => 'el_CY', 'CZE' => 'cs_CZ',
            'DNK' => 'da_DK', 'EGY' => 'ar_EG', 'EST' => 'et_EE', 'FIN' => 'fi_FI',
            'FRA' => 'fr_FR', 'GEO' => 'ka_GE', 'DEU' => 'de_DE', 'GRC' => 'el_GR',
            'HUN' => 'hu_HU', 'ISL' => 'is_IS', 'IND' => 'en_IN', 'IDN' => 'id_ID',
            'IRN' => 'fa_IR', 'ISR' => 'he_IL', 'ITA' => 'it_IT', 'JPN' => 'ja_JP',
            'JOR' => 'ar_JO', 'KAZ' => 'kk_KZ', 'KOR' => 'ko_KR', 'LVA' => 'lv_LV',
            'LTU' => 'lt_LT', 'MYS' => 'ms_MY', 'MDA' => 'ro_MD', 'MNG' => 'mn_MN',
            'NPL' => 'ne_NP', 'NLD' => 'nl_NL', 'NGA' => 'en_NG', 'NOR' => 'nb_NO',
            'PER' => 'es_PE', 'POL' => 'pl_PL', 'PRT' => 'pt_PT', 'ROM' => 'ro_RO',
            'RUS' => 'ru_RU', 'SAU' => 'ar_SA', 'SCG' => 'sr_RS', 'SGP' => 'en_SG',
            'SVK' => 'sk_SK', 'SVN' => 'sl_SI', 'ZAF' => 'en_ZA', 'ESP' => 'es_ES',
            'SWE' => 'sv_SE', 'CHE' => 'de_CH', 'TWN' => 'zh_TW', 'THA' => 'th_TH',
            'TUR' => 'tr_TR', 'UGA' => 'en_UG', 'UKR' => 'uk_UA', 'GBR' => 'en_GB',
            'USA' => 'en_US', 'VEN' => 'es_VE', 'VNM' => 'vi_VN',
        ];

        return $locales[$countryCode] ?? config('app.faker_locale', 'en_US');
    }
}
