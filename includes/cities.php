<?php
// includes/cities.php
// Shared Pakistan cities list — included in all registration and profile forms
// Usage: include this file, then call renderCitySelect($selected, $name, $required)

$pakistan_cities = [
    // Punjab
    'Punjab' => [
        'Lahore', 'Rawalpindi', 'Faisalabad', 'Multan', 'Gujranwala',
        'Sialkot', 'Bahawalpur', 'Sargodha', 'Sheikhupura', 'Jhang',
        'Rahim Yar Khan', 'Gujrat', 'Kasur', 'Sahiwal', 'Okara',
        'Wah Cantt', 'Dera Ghazi Khan', 'Mirpur Khas', 'Chiniot',
        'Kamoke', 'Hafizabad', 'Khanewal', 'Mandi Bahauddin', 'Attock',
        'Jhelum', 'Chakwal', 'Muzaffargarh', 'Vehari', 'Pakpattan',
    ],
    // KPK
    'Khyber Pakhtunkhwa' => [
        'Peshawar', 'Mardan', 'Mingora', 'Kohat', 'Abbottabad',
        'Mansehra', 'Nowshera', 'Charsadda', 'Swat', 'Haripur',
        'Bannu', 'Dera Ismail Khan', 'Lakki Marwat', 'Karak',
    ],
    // Sindh
    'Sindh' => [
        'Karachi', 'Hyderabad', 'Sukkur', 'Larkana', 'Nawabshah',
        'Mirpurkhas', 'Jacobabad', 'Shikarpur', 'Khairpur', 'Dadu',
        'Thatta', 'Badin', 'Tando Adam', 'Tando Allahyar',
    ],
    // Balochistan
    'Balochistan' => [
        'Quetta', 'Turbat', 'Khuzdar', 'Hub', 'Chaman',
        'Gwadar', 'Zhob', 'Loralai', 'Sibi', 'Mastung',
    ],
    // Federal / AJK / GB
    'Federal & Special Areas' => [
        'Islamabad', 'Muzaffarabad', 'Mirpur (AJK)', 'Gilgit',
        'Skardu', 'Chitral',
    ],
];

/**
 * Render a <select> dropdown for city
 *
 * @param string $selected  Currently selected city value
 * @param string $name      HTML name attribute (default: 'city')
 * @param bool   $required  Whether the field is required
 * @param string $class     CSS class (default: 'form-control')
 */
function renderCitySelect($selected = '', $name = 'city', $required = true, $class = 'form-control') {
    global $pakistan_cities;
    $req = $required ? 'required' : '';
    echo "<select name=\"$name\" class=\"$class\" $req>";
    echo "<option value=\"\">-- Select City --</option>";
    foreach ($pakistan_cities as $province => $cities) {
        echo "<optgroup label=\"$province\">";
        foreach ($cities as $city) {
            $sel = ($city === $selected) ? 'selected' : '';
            echo "<option value=\"" . htmlspecialchars($city) . "\" $sel>" . htmlspecialchars($city) . "</option>";
        }
        echo "</optgroup>";
    }
    echo "</select>";
}

/**
 * Get a flat sorted array of all city names
 */
function getAllCities() {
    global $pakistan_cities;
    $flat = [];
    foreach ($pakistan_cities as $cities) {
        $flat = array_merge($flat, $cities);
    }
    sort($flat);
    return $flat;
}
?>