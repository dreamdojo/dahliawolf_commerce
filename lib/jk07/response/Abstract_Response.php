<?php


abstract class Abstract_Response extends Jk_Base
{
	protected $content_type = 'text/plain';
    protected $command;


    protected $valid_ctypes  = array
    (
        'text/plain',
        'text/plain; charset=UTF-8',
        'application/json; charset=UTF-8',
        'image/jpeg',
        'image/gif',
        'image/png',
        'application/zip',
        'application/pdf',
        'application/rar',
        'application/csv',
    );

	
	public function __construct()
	{
		
	}
	
	abstract public function render();

    protected function renderResource($res = null)
    {
        if($res)
        {
            switch (get_resource_type($res))
            {
                case 'gd':

                    //imagejpeg($image_data, '' , 85);
                    switch(self::getContentType())
                    {
                        case 'image/gif':
                            imagegif($res);
                            break;

                        case 'image/png':
                            imagepng($res);
                            break;

                        default:
                            imagejpeg($res, '' , 100);

                    }
                    //imagejpeg($res, '' , 85);
                    ImageDestroy($res);
                    break;

                default:
                    print $res;
                    break;
            }
        }
    }

    
    public function getContentType()
    {
    	return $this->content_type;
    }


    public function setContentType($ct='text/plain; charset=UTF-8')
    {
        if(in_array($ct, $this->valid_ctypes))
        {
            $this->content_type = $ct;
            return true;
        }

        return false;
    }


    protected function execute()
    {
    	$ok = $this->command->execute();

    	$this->data = $this->command->getData();
    	$this->messages = $this->command->getMessages();

        return $ok;
    }
	
	
}