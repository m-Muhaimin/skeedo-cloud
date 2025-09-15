<?php

declare(strict_types=1);

namespace Voice\Domain\ValueObjects;

use JsonSerializable;
use Override;
use Symfony\Component\Intl\Locales;

enum LanguageCode: string implements JsonSerializable
{
    case af_ZA = "af-ZA";
    case am_ET = "am-ET";
    case ar_AE = "ar-AE";
    case ar_BH = "ar-BH";
    case ar_DZ = "ar-DZ";
    case ar_EG = "ar-EG";
    case ar_IQ = "ar-IQ";
    case ar_JO = "ar-JO";
    case ar_KW = "ar-KW";
    case ar_LB = "ar-LB";
    case ar_LY = "ar-LY";
    case ar_MA = "ar-MA";
    case ar_OM = "ar-OM";
    case ar_QA = "ar-QA";
    case ar_SA = "ar-SA";
    case ar_SY = "ar-SY";
    case ar_TN = "ar-TN";
    case ar_XA = "ar-XA";
    case ar_YE = "ar-YE";
    case as_IN = "as-IN";
    case az_AZ = "az-AZ";
    case be_BY = "be-BY";
    case bg_BG = "bg-BG";
    case bn_BD = "bn-BD";
    case bn_IN = "bn-IN";
    case bs_BA = "bs-BA";
    case ca_ES = "ca-ES";
    case cmn_CN = "cmn-CN";
    case cmn_TW = "cmn-TW";
    case cs_CZ = "cs-CZ";
    case cy_GB = "cy-GB";
    case da_DK = "da-DK";
    case de_AT = "de-AT";
    case de_CH = "de-CH";
    case de_DE = "de-DE";
    case el_GR = "el-GR";
    case en_AU = "en-AU";
    case en_CA = "en-CA";
    case en_GB = "en-GB";
    case en_HK = "en-HK";
    case en_IE = "en-IE";
    case en_IN = "en-IN";
    case en_KE = "en-KE";
    case en_NG = "en-NG";
    case en_NZ = "en-NZ";
    case en_PH = "en-PH";
    case en_SG = "en-SG";
    case en_TZ = "en-TZ";
    case en_US = "en-US";
    case en_ZA = "en-ZA";
    case es_AR = "es-AR";
    case es_BO = "es-BO";
    case es_CL = "es-CL";
    case es_CO = "es-CO";
    case es_CR = "es-CR";
    case es_CU = "es-CU";
    case es_DO = "es-DO";
    case es_EC = "es-EC";
    case es_ES = "es-ES";
    case es_GQ = "es-GQ";
    case es_GT = "es-GT";
    case es_HN = "es-HN";
    case es_MX = "es-MX";
    case es_NI = "es-NI";
    case es_PA = "es-PA";
    case es_PE = "es-PE";
    case es_PR = "es-PR";
    case es_PY = "es-PY";
    case es_SV = "es-SV";
    case es_US = "es-US";
    case es_UY = "es-UY";
    case es_VE = "es-VE";
    case et_EE = "et-EE";
    case eu_ES = "eu-ES";
    case fa_IR = "fa-IR";
    case fi_FI = "fi-FI";
    case fil_PH = "fil-PH";
    case fr_BE = "fr-BE";
    case fr_CA = "fr-CA";
    case fr_CH = "fr-CH";
    case fr_FR = "fr-FR";
    case ga_IE = "ga-IE";
    case gl_ES = "gl-ES";
    case gu_IN = "gu-IN";
    case he_IL = "he-IL";
    case hi_IN = "hi-IN";
    case hr_HR = "hr-HR";
    case hu_HU = "hu-HU";
    case hy_AM = "hy-AM";
    case id_ID = "id-ID";
    case is_IS = "is-IS";
    case it_IT = "it-IT";
    case ja_JP = "ja-JP";
    case jv_ID = "jv-ID";
    case ka_GE = "ka-GE";
    case kk_KZ = "kk-KZ";
    case km_KH = "km-KH";
    case kn_IN = "kn-IN";
    case ko_KR = "ko-KR";
    case lo_LA = "lo-LA";
    case lt_LT = "lt-LT";
    case lv_LV = "lv-LV";
    case mk_MK = "mk-MK";
    case mi_NZ = "mi-NZ";
    case ml_IN = "ml-IN";
    case mn_MN = "mn-MN";
    case mr_IN = "mr-IN";
    case ms_MY = "ms-MY";
    case mt_MT = "mt-MT";
    case my_MM = "my-MM";
    case nan_CN = "nan-CN";
    case ne_NP = "ne-NP";
    case nl_BE = "nl-BE";
    case nl_NL = "nl-NL";
    case nb_NO = "nb-NO";
    case or_IN = "or-IN";
    case pa_IN = "pa-IN";
    case pl_PL = "pl-PL";
    case ps_AF = "ps-AF";
    case pt_BR = "pt-BR";
    case pt_PT = "pt-PT";
    case ro_RO = "ro-RO";
    case ru_RU = "ru-RU";
    case si_LK = "si-LK";
    case sk_SK = "sk-SK";
    case sl_SI = "sl-SI";
    case so_SO = "so-SO";
    case sq_AL = "sq-AL";
    case sr_Latn_RS = "sr-Latn-RS";
    case sr_RS = "sr-RS";
    case su_ID = "su-ID";
    case sv_SE = "sv-SE";
    case sw_KE = "sw-KE";
    case sw_TZ = "sw-TZ";
    case ta_IN = "ta-IN";
    case ta_LK = "ta-LK";
    case ta_MY = "ta-MY";
    case ta_SG = "ta-SG";
    case te_IN = "te-IN";
    case th_TH = "th-TH";
    case tl_PH = "tl-PH";
    case tr_TR = "tr-TR";
    case uk_UA = "uk-UA";
    case ur_IN = "ur-IN";
    case ur_PK = "ur-PK";
    case uz_UZ = "uz-UZ";
    case vi_VN = "vi-VN";
    case wuu_CN = "wuu-CN";
    case yue_CN = "yue-CN";
    case yue_HK = "yue-HK";
    case zh_CN = "zh-CN";
    case zh_CN_anhui = "zh-CN-anhui";
    case zh_CN_gansu = "zh-CN-gansu";
    case zh_CN_guangxi = "zh-CN-guangxi";
    case zh_CN_henan = "zh-CN-henan";
    case zh_CN_hunan = "zh-CN-hunan";
    case zh_CN_liaoning = "zh-CN-liaoning";
    case zh_CN_shaanxi = "zh-CN-shaanxi";
    case zh_CN_shandong = "zh-CN-shandong";
    case zh_CN_shanxi = "zh-CN-shanxi";
    case zh_CN_sichuan = "zh-CN-sichuan";
    case zh_HK = "zh-HK";
    case zh_TW = "zh-TW";
    case zu_ZA = "zu-ZA";

    #[Override]
    public function jsonSerialize(): array
    {
        try {
            $name = Locales::getName(
                str_replace('-', '_', $this->value)
            );
        } catch (\Throwable $th) {
            $name = null;
        }

        $parts = explode('-', $this->value);

        return [
            'code' => $this->value,
            'language_code' => $parts[0] ?? null,
            'country_code' => $parts[1] ?? null,
            'name' => $name
        ];
    }

    public static function create(string $val): ?static
    {
        $value = trim($val);
        $map = [
            'yue-HK' => 'zh-HK',
            'cmn-TW' => 'zh-TW',
            'cmn-CN' => 'zh-CN',
            'ar-XA' => 'ar-SA',
            'en-UK' => 'en-GB',
        ];

        $value = $map[$value] ?? $value;

        $value = str_replace([' ', '_'], '-', $value);
        return self::tryFrom($value);
    }
}
