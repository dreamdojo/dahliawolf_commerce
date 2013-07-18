<?php
error_reporting(E_ALL & ~E_NOTICE);
//require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/log/error.php';
class UpsRate
    {
    public function __construct($accessKey, $username, $password, $accountNumber, $shipFromZip, $useTestServer = false)
        {
        $this->access_key = $accessKey;
        $this->username = $username;
        $this->password = $password;
        $this->account_number = $accountNumber;
        $this->ship_from_zip = $shipFromZip;
        if ($useTestServer == false)
            {
            $this->service_uri = "https://www.ups.com/ups.app/xml/Rate";
            }
        if ($useTestServer == false)
            {
            $this->service_uri = "https://www.ups.com/ups.app/xml/Rate";
            }
        }
    private $access_key;
    private $username;
    private $password;
    private $account_number;
    private $ship_from_zip;
    private $service_uri;
    function getRate($shipToZip,$service,$weight,$length,$width,$height)
    {

        if(floatval($weight) < .5) $weight = 1;

        $data ="<?xml version=\"1.0\"?>
        <AccessRequest xml:lang=\"en-US\">
        <AccessLicenseNumber>$this->access_key</AccessLicenseNumber>
        <UserId>$this->username</UserId>
        <Password>$this->password</Password>
        </AccessRequest>
        <?xml version=\"1.0\"?>
        <RatingServiceSelectionRequest xml:lang=\"en-US\">
        <Request>
        <TransactionReference>
        <CustomerContext>Bare Bones Rate Request</CustomerContext>
        <XpciVersion>1.0001</XpciVersion>
        </TransactionReference>
        <RequestAction>Rate</RequestAction>
        <RequestOption>Rate</RequestOption>
        </Request>
        <PickupType>
        <Code>01</Code>
        </PickupType>
        <Shipment>
        <Shipper>
        <Address>
        <PostalCode>$this->ship_from_zip</PostalCode>
        <CountryCode>US</CountryCode>
        </Address>
        <ShipperNumber>$this->account_number</ShipperNumber>
        </Shipper>
        <ShipTo>
        <Address>
        <PostalCode>$shipToZip</PostalCode>
        <CountryCode>US</CountryCode>
        <ResidentialAddressIndicator/>
        </Address>
        </ShipTo>
        <ShipFrom>
        <Address>
        <PostalCode>$this->ship_from_zip</PostalCode>
        <CountryCode>US</CountryCode>
        </Address>
        </ShipFrom>
        <Service>
        <Code>$service</Code>
        </Service>
        <Package>
        <PackagingType>
        <Code>02</Code>
        </PackagingType>
        <Dimensions>
        <UnitOfMeasurement>
        <Code>IN</Code>
        </UnitOfMeasurement>
        <Length>$length</Length>
        <Width>$width</Width>
        <Height>$height</Height>
        </Dimensions>
        <PackageWeight>
        <UnitOfMeasurement>
        <Code>LBS</Code>
        </UnitOfMeasurement>
        <Weight>$weight</Weight>
        </PackageWeight>
        </Package>
        </Shipment>
        </RatingServiceSelectionRequest>";
        $ch = curl_init($this->service_uri);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_TIMEOUT, 60);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $result=curl_exec ($ch);
        //echo '<!-- '. $result. ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS
        $data = strstr($result, '<?');
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
                while($start_level < $xml_elem['level'])
                    {
                    $php_stmt .= '[$level['.$start_level.']]';
                    $start_level++;
                    }
                $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                eval($php_stmt);
                }
            }
        curl_close($ch);
        return $params['RATINGSERVICESELECTIONRESPONSE']['RATEDSHIPMENT']['TOTALCHARGES']['MONETARYVALUE'];
        }
    }
?>