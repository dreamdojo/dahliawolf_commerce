<?php

    class Crypt
    {
        private $securekey, $iv;
    
    
        function __construct( $textkey )
        {
            $this->securekey = hash( 'sha256', $textkey, true );
            $this->iv = mcrypt_create_iv( 32, MCRYPT_RAND );
        }
    
        function encrypt( $input )
        {
            return trim( base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $this->securekey, $input, MCRYPT_MODE_ECB, $this->iv ) ), '=' );
        }
    
        function decrypt( $input )
        {
            return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $this->securekey, base64_decode( $input ), MCRYPT_MODE_ECB, $this->iv ) );
        }
    }

?>