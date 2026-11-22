<?php

namespace App\Support;

class CountryDialCodes
{
    /**
     * Return full list of countries with dialing code, ISO2, flag emoji, short display abbreviation, and full name.
     *
     * @return array<int, array{dial: string, iso: string, abbr: string, flag: string, name: string, label: string}>
     */
    public static function all(): array
    {
        $priority = [
            ['+44', 'GB', 'UK', '🇬🇧', 'United Kingdom'],
            ['+880', 'BD', 'BAN', '🇧🇩', 'Bangladesh'],
            ['+1', 'US', 'USA', '🇺🇸', 'United States'],
            ['+91', 'IN', 'IND', '🇮🇳', 'India'],
            ['+61', 'AU', 'AUS', '🇦🇺', 'Australia'],
            ['+971', 'AE', 'UAE', '🇦🇪', 'United Arab Emirates'],
        ];

        $rest = [
            ['+93', 'AF', 'AFG', '🇦🇫', 'Afghanistan'],
            ['+355', 'AL', 'ALB', '🇦🇱', 'Albania'],
            ['+213', 'DZ', 'DZA', '🇩🇿', 'Algeria'],
            ['+376', 'AD', 'AND', '🇦🇩', 'Andorra'],
            ['+244', 'AO', 'AGO', '🇦🇴', 'Angola'],
            ['+54', 'AR', 'ARG', '🇦🇷', 'Argentina'],
            ['+374', 'AM', 'ARM', '🇦🇲', 'Armenia'],
            ['+43', 'AT', 'AUT', '🇦🇹', 'Austria'],
            ['+994', 'AZ', 'AZE', '🇦🇿', 'Azerbaijan'],
            ['+973', 'BH', 'BHR', '🇧🇭', 'Bahrain'],
            ['+375', 'BY', 'BLR', '🇧🇾', 'Belarus'],
            ['+32', 'BE', 'BEL', '🇧🇪', 'Belgium'],
            ['+501', 'BZ', 'BLZ', '🇧🇿', 'Belize'],
            ['+229', 'BJ', 'BEN', '🇧🇯', 'Benin'],
            ['+975', 'BT', 'BTN', '🇧🇹', 'Bhutan'],
            ['+591', 'BO', 'BOL', '🇧🇴', 'Bolivia'],
            ['+387', 'BA', 'BIH', '🇧🇦', 'Bosnia and Herzegovina'],
            ['+267', 'BW', 'BWA', '🇧🇼', 'Botswana'],
            ['+55', 'BR', 'BRA', '🇧🇷', 'Brazil'],
            ['+673', 'BN', 'BRN', '🇧🇳', 'Brunei'],
            ['+359', 'BG', 'BGR', '🇧🇬', 'Bulgaria'],
            ['+855', 'KH', 'KHM', '🇰🇭', 'Cambodia'],
            ['+237', 'CM', 'CMR', '🇨🇲', 'Cameroon'],
            ['+1', 'CA', 'CAN', '🇨🇦', 'Canada'],
            ['+56', 'CL', 'CHL', '🇨🇱', 'Chile'],
            ['+86', 'CN', 'CHN', '🇨🇳', 'China'],
            ['+57', 'CO', 'COL', '🇨🇴', 'Colombia'],
            ['+506', 'CR', 'CRI', '🇨🇷', 'Costa Rica'],
            ['+385', 'HR', 'HRV', '🇭🇷', 'Croatia'],
            ['+53', 'CU', 'CUB', '🇨🇺', 'Cuba'],
            ['+357', 'CY', 'CYP', '🇨🇾', 'Cyprus'],
            ['+420', 'CZ', 'CZE', '🇨🇿', 'Czech Republic'],
            ['+45', 'DK', 'DNK', '🇩🇰', 'Denmark'],
            ['+593', 'EC', 'ECU', '🇪🇨', 'Ecuador'],
            ['+20', 'EG', 'EGY', '🇪🇬', 'Egypt'],
            ['+372', 'EE', 'EST', '🇪🇪', 'Estonia'],
            ['+251', 'ET', 'ETH', '🇪🇹', 'Ethiopia'],
            ['+679', 'FJ', 'FJI', '🇫🇯', 'Fiji'],
            ['+358', 'FI', 'FIN', '🇫🇮', 'Finland'],
            ['+33', 'FR', 'FRA', '🇫🇷', 'France'],
            ['+995', 'GE', 'GEO', '🇬🇪', 'Georgia'],
            ['+49', 'DE', 'DEU', '🇩🇪', 'Germany'],
            ['+233', 'GH', 'GHA', '🇬🇭', 'Ghana'],
            ['+30', 'GR', 'GRC', '🇬🇷', 'Greece'],
            ['+852', 'HK', 'HKG', '🇭🇰', 'Hong Kong'],
            ['+36', 'HU', 'HUN', '🇭🇺', 'Hungary'],
            ['+354', 'IS', 'ISL', '🇮🇸', 'Iceland'],
            ['+62', 'ID', 'IDN', '🇮🇩', 'Indonesia'],
            ['+98', 'IR', 'IRN', '🇮🇷', 'Iran'],
            ['+964', 'IQ', 'IRQ', '🇮🇶', 'Iraq'],
            ['+353', 'IE', 'IRL', '🇮🇪', 'Ireland'],
            ['+972', 'IL', 'ISR', '🇮🇱', 'Israel'],
            ['+39', 'IT', 'ITA', '🇮🇹', 'Italy'],
            ['+81', 'JP', 'JPN', '🇯🇵', 'Japan'],
            ['+962', 'JO', 'JOR', '🇯🇴', 'Jordan'],
            ['+7', 'KZ', 'KAZ', '🇰🇿', 'Kazakhstan'],
            ['+254', 'KE', 'KEN', '🇰🇪', 'Kenya'],
            ['+965', 'KW', 'KWT', '🇰🇼', 'Kuwait'],
            ['+996', 'KG', 'KGZ', '🇰🇬', 'Kyrgyzstan'],
            ['+856', 'LA', 'LAO', '🇱🇦', 'Laos'],
            ['+371', 'LV', 'LVA', '🇱🇻', 'Latvia'],
            ['+961', 'LB', 'LBN', '🇱🇧', 'Lebanon'],
            ['+218', 'LY', 'LBY', '🇱🇾', 'Libya'],
            ['+370', 'LT', 'LTU', '🇱🇹', 'Lithuania'],
            ['+352', 'LU', 'LUX', '🇱🇺', 'Luxembourg'],
            ['+853', 'MO', 'MAC', '🇲🇴', 'Macau'],
            ['+60', 'MY', 'MYS', '🇲🇾', 'Malaysia'],
            ['+960', 'MV', 'MDV', '🇲🇻', 'Maldives'],
            ['+356', 'MT', 'MLT', '🇲🇹', 'Malta'],
            ['+230', 'MU', 'MUS', '🇲🇺', 'Mauritius'],
            ['+52', 'MX', 'MEX', '🇲🇽', 'Mexico'],
            ['+373', 'MD', 'MDA', '🇲🇩', 'Moldova'],
            ['+377', 'MC', 'MCO', '🇲🇨', 'Monaco'],
            ['+976', 'MN', 'MNG', '🇲🇳', 'Mongolia'],
            ['+212', 'MA', 'MAR', '🇲🇦', 'Morocco'],
            ['+95', 'MM', 'MMR', '🇲🇲', 'Myanmar'],
            ['+977', 'NP', 'NPL', '🇳🇵', 'Nepal'],
            ['+31', 'NL', 'NLD', '🇳🇱', 'Netherlands'],
            ['+64', 'NZ', 'NZL', '🇳🇿', 'New Zealand'],
            ['+234', 'NG', 'NGA', '🇳🇬', 'Nigeria'],
            ['+47', 'NO', 'NOR', '🇳🇴', 'Norway'],
            ['+968', 'OM', 'OMN', '🇴🇲', 'Oman'],
            ['+92', 'PK', 'PAK', '🇵🇰', 'Pakistan'],
            ['+970', 'PS', 'PSE', '🇵🇸', 'Palestine'],
            ['+507', 'PA', 'PAN', '🇵🇦', 'Panama'],
            ['+51', 'PE', 'PER', '🇵🇪', 'Peru'],
            ['+63', 'PH', 'PHL', '🇵🇭', 'Philippines'],
            ['+48', 'PL', 'POL', '🇵🇱', 'Poland'],
            ['+351', 'PT', 'PRT', '🇵🇹', 'Portugal'],
            ['+974', 'QA', 'QAT', '🇶🇦', 'Qatar'],
            ['+40', 'RO', 'ROU', '🇷🇴', 'Romania'],
            ['+7', 'RU', 'RUS', '🇷🇺', 'Russia'],
            ['+966', 'SA', 'SAU', '🇸🇦', 'Saudi Arabia'],
            ['+381', 'RS', 'SRB', '🇷🇸', 'Serbia'],
            ['+65', 'SG', 'SGP', '🇸🇬', 'Singapore'],
            ['+421', 'SK', 'SVK', '🇸🇰', 'Slovakia'],
            ['+386', 'SI', 'SVN', '🇸🇮', 'Slovenia'],
            ['+27', 'ZA', 'ZAF', '🇿🇦', 'South Africa'],
            ['+82', 'KR', 'KOR', '🇰🇷', 'South Korea'],
            ['+34', 'ES', 'ESP', '🇪🇸', 'Spain'],
            ['+94', 'LK', 'LKA', '🇱🇰', 'Sri Lanka'],
            ['+46', 'SE', 'SWE', '🇸🇪', 'Sweden'],
            ['+41', 'CH', 'CHE', '🇨🇭', 'Switzerland'],
            ['+886', 'TW', 'TWN', '🇹🇼', 'Taiwan'],
            ['+255', 'TZ', 'TZA', '🇹🇿', 'Tanzania'],
            ['+66', 'TH', 'THA', '🇹🇭', 'Thailand'],
            ['+216', 'TN', 'TUN', '🇹🇳', 'Tunisia'],
            ['+90', 'TR', 'TUR', '🇹🇷', 'Turkey'],
            ['+256', 'UG', 'UGA', '🇺🇬', 'Uganda'],
            ['+380', 'UA', 'UKR', '🇺🇦', 'Ukraine'],
            ['+598', 'UY', 'URY', '🇺🇾', 'Uruguay'],
            ['+998', 'UZ', 'UZB', '🇺🇿', 'Uzbekistan'],
            ['+58', 'VE', 'VEN', '🇻🇪', 'Venezuela'],
            ['+84', 'VN', 'VNM', '🇻🇳', 'Vietnam'],
            ['+260', 'ZM', 'ZMB', '🇿🇲', 'Zambia'],
            ['+263', 'ZW', 'ZWE', '🇿🇼', 'Zimbabwe'],
        ];

        $format = fn (array $row): array => [
            'dial' => $row[0],
            'iso' => $row[1],
            'abbr' => $row[2],
            'flag' => $row[3],
            'name' => $row[4],
            'label' => "{$row[0]} {$row[3]} {$row[2]}",
        ];

        return array_map($format, array_merge($priority, $rest));
    }

    /**
     * Find a country entry by dialing code (and optional ISO code).
     */
    public static function findByDial(string $dial, ?string $iso = null): array
    {
        $all = self::all();
        if ($iso) {
            foreach ($all as $item) {
                if ($item['dial'] === $dial && strtoupper($item['iso']) === strtoupper($iso)) {
                    return $item;
                }
            }
        }
        foreach ($all as $item) {
            if ($item['dial'] === $dial) {
                return $item;
            }
        }

        return $all[0];
    }
}
