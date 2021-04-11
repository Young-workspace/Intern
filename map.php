<?  
    // receive ajax menu data
    $menu = $_POST['menu'];

    // parse received ajax params data and save to postArr
    parse_str($_POST['params'], $postArr);

    // header array contains ID and Key value
    $header = array();
    $header[] = ""; // key
    $header[] = ""; // key

    // goto each api function according to selected menu
    if($menu == "geocode"){
        // addr: input address
        $addr = $postArr['addr'];

        //res: response from geocode function
        $res = geocode($addr, $header);
    }
    else if($menu == "search"){
        // location: input location
        $location = $postArr['location'];

        // x_cor: input x coordinate
        $x_cor = $postArr['x_cor'];

        // y_cor: input y coordinate
        $y_cor = $postArr['y_cor'];

        $res = search($location, $x_cor, $y_cor, $header);
    }
    else if($menu == "gc"){
        // x_cor: input x coordinate
        $x_cor = $postArr['x_cor_gc'];

        // y_cor: input y coordinate
        $y_cor = $postArr['y_cor_gc'];
        $res = gc($x_cor, $y_cor, $header);
    }
    echo json_encode($res);


// geocoding api function
function geocode($addr, $header)
{
    // naver geocode api path
    $path = "https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode";

    // save input address to $req, and request $req to naver api
    // must use $req["query"]
    $req = array();
    $req["query"] = $addr;

    // option
    $opt = array();
    $opt["is_get"]  =   TRUE;
    $opt["is_json"] =   FALSE;
    $opt["is_multipart"] =  FALSE;

    return sendCURL($path, $header, $req, $opt);


}

// searching api function
function search($location, $x_cor, $y_cor, $header){
    // naver searching api path
    $path = "https://naveropenapi.apigw.ntruss.com/map-place/v1/search";

    // save location, coordinates(x,y) and request to naver api
    // must use $req["query"], $req["cooordinate"]
    $req = array();
    $req["query"] = $location;
    $req["coordinate"] = $x_cor. "," .$y_cor;

    $opt = array();
    $opt["is_get"]  =   TRUE;
    $opt["is_json"] =   FALSE;
    $opt["is_multipart"] =  FALSE;

    return sendCURL($path, $header, $req, $opt);

}

// reverse geocoding api function
function gc($x_cor, $y_cor, $header){
    // naver reverse geocoding api path
    $path = "https://naveropenapi.apigw.ntruss.com/map-reversegeocode/v2/gc";    
    // coords and output must be used
    $req = array();
    //$req["request"] = "coordsToaddr";
    $req["coords"] = $x_cor. "," .$y_cor;
    //$req["sourcecrs"] = "epsg:4326";
    //$req["orders"] = "admcode,legalcode,addr,roadaddr";
    $req["output"] = "json";

    $opt = array();
    $opt["is_get"]  =   TRUE;
    $opt["is_json"] =   FALSE;
    $opt["is_multipart"] =  FALSE;

    return sendCURL($path, $header, $req, $opt);
}

나머지 sendCURL은 이전하고 똑같음