<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/log/error.php';
class UspsRate
    {
    public function __construct($username)
        {
        $this->username = $username;
        $this->test_call = false;
        }
    private $username;    
    private $test_call;
    private $server_uri;
    private $api_name;
    private $xml_data;    
    private $raw_response;
    private $xml_response;
    private $array_response;
    private function callWebtools()
        {
        switch ($this->test_call)
            {
            case true:
                $this->server_uri = "http://testing.shippingapis.com/ShippingAPITest.dll";
                break;
            case false:
                $this->server_uri ="http://production.shippingapis.com/ShippingAPI.dll";
            }
        $queryString = "API=$this->api_name&XML=$this->xml_data";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->server_uri);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$queryString);
        $result=curl_exec ($ch);
        curl_close($ch);
        $this->raw_response = $result;
        $xml_result = strstr($result, '<?');
        if ($xml_result == "")
            {
            $this->xml_response = "<?xml version=\"1.0\"?>\r\n" . strstr($result, '<Error></Error>');
            }
        else
            {
            $this->xml_response = $xml_result;
            }
        $this->convertResponseToArray();
        }
    private function convertResponseToArray()
        {
        $data = $this->xml_response;
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $data, $vals, $index);
        xml_parser_free($xml_parser);
        $params = array();
        $level = array();
        foreach ($vals as $xml_elem)
            {
            if ($xml_elem['type'] == 'open')
                {
                if (array_key_exists('attributes',$xml_elem))
                    {
                    list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
                    }
                else
                    {
                    $level[$xml_elem['level']] = $xml_elem['tag'];
                    }
                }
            if ($xml_elem['type'] == 'complete')
                {
                $start_level = 1;
                $php_stmt = '$params';
                $xml_elem;
                while($start_level < $xml_elem['level'])
                    {
                    $php_stmt .= '[$level['.$start_level.']]';
                    $start_level++;
                    }
                $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                eval($php_stmt);
                }
            }
        $this->array_response = $params;
        }
    public function testCall1()
        {
        $this->test_call = true;
        $this->api_name = "Verify";
        $this->xml_data = "<AddressValidateRequest%20USERID=\"$this->username\"><Address ID=\"0\"><Address1></Address1><Address2>6406 Ivy Lane</Address2><City>Greenbelt</City><State>MD</State><Zip5></Zip5><Zip4></Zip4></Address></AddressValidateRequest>";
        $this->callWebtools();
        return $this->array_response['ADDRESSVALIDATERESPONSE'];
        }
    public function testCall2()
        {
        $this->test_call = true;
        $this->api_name = "Verify";
        $this->xml_data = "<AddressValidateRequest%20USERID=\"$this->username\"><Address ID=\"1\"><Address1></Address1><Address2>8 Wildwood Drive</Address2><City>Old Lyme</City><State>CT</State><Zip5>06371</Zip5><Zip4></Zip4></Address></AddressValidateRequest>";
        $this->callWebtools();
        return $this->array_response['ADDRESSVALIDATERESPONSE'];
        }
    public function testCall3()
        {
        $this->api_name = "RateV4";
        $this->xml_data = "<RateV4Request USERID=\"$this->username\"><Revision/><Package ID=\"1ST\"><Service>FIRST CLASS</Service><FirstClassMailType>LETTER</FirstClassMailType><ZipOrigination>44106</ZipOrigination><ZipDestination>20770</ZipDestination><Pounds>0</Pounds><Ounces>3.5</Ounces><Container/><Size>REGULAR</Size><Machinable>true</Machinable></Package></RateV4Request>";
        $this->callWebtools();
        return $this->array_response['RATEV4RESPONSE']['1ST'][0]['RATE'];
        }
    public function getRate($service, $firstClassMailType, $sendFromZip, $sendToZip, $pounds, $ounces, $containerSize, $machinable = "true")
        {
        $this->api_name = "RateV4";
        $this->xml_data = "<RateV4Request USERID=\"$this->username\"><Revision/><Package ID=\"1ST\"><Service>$service</Service><FirstClassMailType>$firstClassMailType</FirstClassMailType><ZipOrigination>$sendFromZip</ZipOrigination><ZipDestination>$sendToZip</ZipDestination><Pounds>$pounds</Pounds><Ounces>$ounces</Ounces><Container/><Size>$containerSize</Size><Machinable>$machinable</Machinable></Package></RateV4Request>";
        $this->callWebtools();
        return $this->array_response['RATEV4RESPONSE']['1ST'][0]['RATE'];
        }
    }
?>