<?php
	
	class Jk_Logger
	{
        const DEBUG         = 1;	// Most Verbose
        const INFO          = 2;	// ...
        const WARN          = 3;	// ...
        const ERROR         = 4;	// ...
        const FATAL         = 5;	// Least Verbose
        const OFF           = 6;	// Nothing at all.
        
        const LOG_OPEN      = 1;
        const OPEN_FAILED   = 2;
        const LOG_CLOSED    = 3;
		

		public $Log_Status 	= Jk_Logger::LOG_CLOSED;
		public $DateFormat	= "Y-m-d h:i:s A";
		public $MessageQueue;
        //public $maxsize     = 104857600; //100MB
        public $maxsize     = 26214400; //100MB

		private $log_file;
		private $priority = Jk_Logger::INFO;
		
		private $file_handle;
        
		
		public function __construct( $filepath , $priority=Jk_Logger::DEBUG )
		{
			if ( $priority == Jk_Logger::OFF ) return;
			
			$this->log_file = $filepath;
			$this->MessageQueue = array();
			$this->priority = $priority;

            $this->initFile();
		}

        private function initFile()
        {
            if ( file_exists( $this->log_file ) )
            {
                if ( !is_writable($this->log_file) )
                {
                    $this->Log_Status = Jk_Logger::OPEN_FAILED;
                    $this->MessageQueue[] = "The file exists, but could not be opened for writing. Check that appropriate permissions have been set.";
                }
            }else
            {
                file_put_contents($this->log_file, '');
            }


			if ( $this->file_handle = fopen($this->log_file, "a") )
			{
				$this->Log_Status = Jk_Logger::LOG_OPEN;
				$this->MessageQueue[] = "The log file was opened successfully.";
			}
			else
			{
				$this->Log_Status = Jk_Logger::OPEN_FAILED;
				$this->MessageQueue[] = "The file could not be opened. Check permissions.";
			}
        }
		
		public function __destruct()
		{
			
			if ($this->file_handle)
			{
				@fclose($this->file_handle);
			}
		}


        public function unBuffer()
        {
            stream_set_write_buffer($this->file_handle, 0);
        }
		
		public function LogInfo($line)
		{
			$this->Log( $line , Jk_Logger::INFO );
		}
		
		public function LogDebug($line)
		{
			$this->Log( $line , Jk_Logger::DEBUG );
		}
		
		public function LogWarn($line)
		{
			$this->Log( $line , Jk_Logger::WARN );	
		}
		
		public function LogError($line)
		{
			$this->Log( $line , Jk_Logger::ERROR );	
		}

		public function LogFatal($line)
		{
			$this->Log( $line , Jk_Logger::FATAL );
		}
		
		public function Log($line, $priority)
		{
			if ( $this->priority <= $priority )
			{
                $this->clearLogFile();
				$status = $this->getTimeLine( $priority );
				$this->WriteFreeFormLine ( "$status $line \n\r" );
			}
		}

        public function clearLogFile($force = false)
        {
            if($force) file_put_contents($this->log_file, '');

            if($this->maxsize > 0 && @filesize($this->log_file) > $this->maxsize)
            {
                fclose($this->file_handle );
                @rename( $this->log_file,  "{$this->log_file}_".date('Y-m-d') );
                $this->initFile();
            }
        }

        public static function deleteAll($path = false)
        {
            $folder = ($path ? $path : APP_PATH) .'logs/';
            $logs = Jk_Functions::readFolderAll( $folder );
            foreach($logs as $log) unlink($folder.$log);
        }


		public function WriteFreeFormLine( $line )
		{
			if ( $this->Log_Status == Jk_Logger::LOG_OPEN && $this->priority != Jk_Logger::OFF )
			{
			    if (fwrite($this->file_handle, $line) === false) {
			        $this->MessageQueue[] = "The file could not be written to. Check that appropriate permissions have been set.";
			        echo "The file could not be written to. Check that appropriate permissions have been set. $line ". get_resource_type($this->file_handle);
			    }
			}
		}
		
		public function setPriority($p)
		{
			$this->priority  = $p;
		}
		
		private function getTimeLine( $level )
		{
			$time = date( $this->DateFormat, time());
		
			switch( $level )
			{
				case Jk_Logger::INFO:
					return "$time - INFO  -->";
				case Jk_Logger::WARN:
					return "$time - WARN  -->";				
				case Jk_Logger::DEBUG:
					return "$time - DEBUG -->";				
				case Jk_Logger::ERROR:
					return "$time - ERROR -->";
				case Jk_Logger::FATAL:
					return "$time - FATAL -->";
				default:
					return "$time - LOG   -->";
			}
		}
		
	}
	
	
?>