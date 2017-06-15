<?php
//To run this example use the following URL
//{Your Domain}/Here_PDE_Demo.php?coords={"lat": 30.192327, "lng": -81.727523}


if ($_GET['coords']) {
    $coords = json_decode($_GET['coords'], true);

    $ch = curl_init("https://reverse.geocoder.api.here.com/6.2/reversegeocode.json?prox=" . $coords['lat'] . "," . $coords['lng'] . ",50&mode=retrieveAddresses&locationAttributes=linkInfo&gen=9&app_id={YOUR_APP_ID}&app_code={YOUR_APP_CODE}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (gettype($data) == "array") {
        $ReferenceId = $data["Response"]["View"][0]["Result"][0]["Location"]["MapReference"]["ReferenceId"];
        $functionClass = $data["Response"]["View"][0]["Result"][0]["Location"]["LinkInfo"]["FunctionalClass"];

        if (isset($ReferenceId) && isset($functionClass)) {
            $level = 8 + $functionClass;
            $tileSize = 180 / pow(2, $level);
            $tileY = (int)(($coords['lat'] + 90) / $tileSize);
            $tileX = (int)(($coords['lng'] + 180) / $tileSize);

            $ch = curl_init("https://pde.api.here.com/1/tile.json?layer=SPEED_LIMITS_FC" . (string)$functionClass . "&tilex=" . $tileX . "&tiley=" . $tileY . "&level=" . $level . "&app_id={YOUR_APP_ID}&app_code={YOUR_APP_CODE}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $pde = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (gettype($pde) == "array") {
                $pdeLength = count($pde["Rows"]);
                $txt = "No Speed found for supplied location";
                for ($i = 0; $i < $pdeLength; $i++) {
                    if ($pde["Rows"][$i]["LINK_ID"] == $ReferenceId) {
                        if ($pde["Rows"][$i]["FROM_REF_SPEED_LIMIT"]) {
                            $Speed = floatval($pde["Rows"][$i]["FROM_REF_SPEED_LIMIT"]);
                            $Speed = (int)($Speed * 0.621371); //convert from KPH to MPH
                            $txt = $Speed . "mph";
                        } elseif ($pde["Rows"][$i]["TO_REF_SPEED_LIMIT"]) {
                            $Speed = floatval($pde["Rows"][$i]["TO_REF_SPEED_LIMIT"]);
                            $Speed = (int)($Speed * 0.621371); //convert from KPH to MPH
                            $txt = $Speed . "mph";
                        }

                        break;
                    }
                }
                echo $txt;
            }
        }
    }
}
