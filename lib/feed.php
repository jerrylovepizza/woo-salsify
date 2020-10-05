<?php

class SOD_Feed {

    public $api_url = "https://app.salsify.com/api/";

    public $compare1 = ["equals", "doesn't equal", "has any value", "has no value", "is between", "is greater than", "is less than", "is valid", "is invalid"];

    public $compare2 = ["equals", "doesn't equal", "has any value", "has no value", "contains", "doesn't contain", "starts with", "doesn't start with", "contains word that starts with", "doesn't contain word that starts with", "is valid", "is invalid"];

    public function get_product($page = null, $selected = 0) {

        $url = "/products/";
        $options = Salsify_Plugin::get_options();
        $filter = $options["filter"][$selected];

        // var_dump ($filter);

        if ($filter) {
            $url = $url . "?filter=" . urlencode("=" .$filter) . "&";
        } else {
            $url = $url . "?";
        }

        if ($page) {
            $p = $page["page"];
            $per_page = $page["per_page"];

            $url = $url . "&page=$p&per_page=$per_page";
        }


        return $this->get_data($url);
    }

    public function update_product($selected, $product_id, $stock) {
        $url = "/products/";
        $options = Salsify_Plugin::get_options();
        
        $sal_product_id = get_post_meta($product_id, "_product_id", true);

        if (!$sal_product_id) {
            return false;
        }

        if (isset($options["account"][$selected]["activate"])) {

            $field = isset($options["product"][$selected]["stock_quantity"]) ? $options["product"][$selected]["stock_quantity"] : "";


            if ($field) {
                $field = $field[0];    
                $url = "/products/" . $sal_product_id;

                $this->update_data($url, $selected, array($field => $stock));
            }
        }

        return true;
    }

    private function update_data($url, $selected = 0, $data) {
        $options = Salsify_Plugin::get_options();
        $url = $this->api_url . "orgs/". $options["account"][$selected]["org"] . $url;        

        $ch = curl_init($url);

        $authorization = "Authorization: Bearer " . $options["account"][$selected]["api"];
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    private function get_data($url, $selected = 0, $version = "v1", $options = null) {

        if (!$options)
            $options = Salsify_Plugin::get_options();

        $url = $this->api_url . $version . "/orgs/" . $options["account"][$selected]["org"] . $url;        
        // echo $url;
        $ch = curl_init($url);        
        $authorization = "Authorization: Bearer " . $options["account"][$selected]["api"];
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    public function get_property_from_api($selected = 0, $page = 1, $options = null) {
        $url = "/properties/?per_page=100&page=" . $page;

        $data = $this->get_data($url, $selected, "", $options);
        
        $properties = [];
        
        if (isset($data["meta"])) {
            $meta = $data["meta"];
            $total = $meta["total_entries"];

            $property = isset($data["properties"]) ? $data["properties"] : [];

            foreach ($property  as $p) {
                // if (strpos($p["property_group"], "Digital Assets") !== false) {
                    $properties[$p["property_group"]][] = $p["id"];
                // }
            }

            while (ceil($total / 100) >= $page) {
                
                $page ++;
                $url = "/properties/?per_page=100&page=" . $page;

                $data = $this->get_data($url, $selected, "", $options);


                $property = isset($data["properties"]) ? $data["properties"] : [];

                foreach ($property  as $p) {
                    // if (strpos($p["property_group"], "Digital Assets") !== false) {
                        $properties[$p["property_group"]][] = $p["id"];
                    // }
                }

            }
        }

        if (count($properties)) {
            update_option('salsify_property_' . $selected, $properties);
        }
    }

    public function get_property($selected = 0) {

        $data = get_option('salsify_property_' . $selected, false);

        if ($data) {
            $properties = [];
            foreach ($data as $d) {
                ksort($d);
                // $properties = array_merge($properties, $d);
                foreach ($d as $p) {
                    $properties[$p] = $p;
                }
            }

            return $properties;
        }

        return false;
        // $url = "/properties/?per_page=1&";

        // $data = $this->get_data($url, $selected);

        // if (isset($data["data"])) {
        //     $data = $data["data"][0];
        //     $properties = [];
        //     foreach ($data as $key => $val) {
        //         if (strpos ($key, "salsify:") === false)
        //             $properties[$key] = is_numeric($val) ? true : false;
                
        //     }

        //     ksort($properties);

        //     return $properties;
        // }

        // return [];
    }

    public function filter_maker($options, $selected = 0) {
        // /$selected = isset($options["selected"]) ? $options["selected"] : 0;
        $count = count($options["feed"][$selected]["property"]);
        $outs = [];
        for ($i = 0; $i < $count; $i++) {
            $property = $options["feed"][$selected]["property"][$i];
            $condition = $options["feed"][$selected]["condition"][$i];
            $val = $options["feed"][$selected]["value1"][$i];

            if ($property == "" || $condition == "") {
                continue;
            }

            $out = "'$property'";
            switch ($condition) {
                case "equals":
                    $out = $out . ":'$val'";
                break;
                case "doesn't equal":
                    $out = $out . ":^'$val'";
                break;
                case "has any value":
                    $out = $out . ":*";
                break;
                case "has no value":
                    $out = $out . ":^*";
                break;
                case "is between":
                    $val1 = $options["feed"][$selected]["value2"][$i];
                    $out = $out . ":range($val, $val1)";
                break; 
                case "is greater than":
                    $out = $out . ":gt($val)";
                break;
                case "is less than":
                    $out = $out . ":lt($val)";
                break;
                case "is valid":
                    $out = $out . ":valid()";
                break;
                case "is invalid":
                    $out = $out . ":^valid()";
                break;
                case "contains":
                    $out = $out . ":contains('$val')";
                break;
                case "doesn't contain":
                    $out = $out . ":^contains('$val')";
                break;
                case "starts with":
                    $out = $out . ":starts_with('$val')";
                break;
                case "doesn't start with":
                    $out = $out . ":^starts_with('$val')";
                break;
                case "contains word that starts with":
                    $out = $out . ":~'$val'";
                break;
                case "doesn't contain word that starts with":
                    $out = $out . ":^~'$val'";
                break;
            }

            $outs[] = $out;
        }


        if (empty($outs)) {
            return "";
        }
        
        return implode(",", $outs);
    }
}