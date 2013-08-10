<?php


class Jk_Loader
{
    private static $verbose = true;

    protected static    $app_root = null;
    protected static    $include_dirs = array
                        (
                            'core',
                            'models',
                            'modules',
                            'controllers',
                        );


    public function __construct()
    {
        self::setAppRoot(APP_PATH);
    }

    public static function addLoadSource($dir)
    {
        self::$include_dirs[] = $dir;
    }

    public static function setAppRoot($dir)
    {
        if(file_exists($dir)) self::$app_root = $dir;
    }


    public static function getAppRoot()
    {
        return self::$app_root;
    }


    public static function boot( $modules = null, $core_classes = null )
    {
        //// register init time ////
        list($usec, $sec) = explode(' ',microtime());
        $init_time = ((float)$usec + (float)$sec);
        define('EXECUTION_INIT', $init_time );


        //load main core classes
        self::loadCorePackage('core');


        //load core modules
        //foreach( array("response", "utils") as $module)
        foreach( array("response", "utils", 'controllers') as $module)
        {
            self::loadCoreModule($module);
        }

        
        //load core models user and site
        $load_core_models = array('user', 'site');
        foreach($load_core_models as $model)  self::loadModel($model);
        
        //// load any extra modules
        if(is_array($modules))
        {
            foreach($modules as $module) self::loadModel($module);
        }

        //init core classes
        $load_core_classes = array('Jk_Request', 'Jk_Session');

        if(is_array($core_classes)) $load_core_classes = array_merge($load_core_classes, $core_classes);

        foreach ($load_core_classes as $class) $class::getInstance();

        /*
        Jk_Request::getInstance();
        Jk_Session::getInstance();
        */
    }

    public static function loadController( $class )
    {
        $controller = strpos($class, "_Controller") > -1 ? $class : ucfirst($class) . "_Controller";
        Jk_Loader::loadClass($controller, "controllers");
    }


    public static function loadInterface($class)
    {
        $class = ucfirst($class);
        Jk_Loader::loadClass("interfaces#{$class}");
    }

	public static function loadClass($class_name, $scope = false)
	{
		$module = '';
		$class  = $class_name;

		if( strpos($class_name,"#") > -1 )
		{
			$module = substr($class_name, 0, strrpos($class_name, "#")). '/';
			$class  = substr($class_name, strrpos($class_name, "#") + 1);
		}

        //Jk_Base::debug("module: $module class: $class");

		foreach( self::$include_dirs as $dir)
		{
            //Jk_Base::debug("scope: $scope curent: $dir");
			if($scope != false && $scope != $dir) continue;

			if( $files = self::readIncludeFolder( "$dir/$module", array('php'), array('')) )
			{
				foreach($files as $file)
				{
					$name   = substr(strtolower($file), strrpos($file, "/")+1, strrpos($file, ".")-(strrpos($file, "/")+1));
					//$ext    = substr(strtolower($file), strrpos($file, ".") + 1);
                    //$name   = substr(basename($file), 0, strrpos($file, "."));

					if( $name == strtolower($class) )
					{
						if(class_exists($class) == false)
							include_once($file);
						break;
					}
				}
			}
		}

    }


    public static function loadCorePackage($package, $baseclass = null)
    {
        self::loadPackage($package, $baseclass, true, $baseclass);
    }


    public static function loadCoreModule($module, $baseclass = null)
    {
        self::loadPackage($module, 'core', false, $baseclass);
    }


    public static function loadModel($module, $baseclass = null)
    {
        self::loadPackage($module, 'models', false, $baseclass);
    }


    public static function loadModule( $module, $baseclass = null )
    {
        self::loadPackage($module, 'modules', false, $baseclass);
    }


	public static function loadPackage( $module, $scope = null, $iscore = false, $baseclass = null )
	{
        $baseclass  = ($baseclass ? $baseclass : ucfirst($module) . '_Base' );

        //load a core dir/module
        if($iscore)
        {
            self::loadClasspath( $module , $baseclass);
            return;
        }

        //load only from these paths
		foreach( self::$include_dirs as $dir)
		{
			if($scope != false && $scope != $dir) continue;

            $module = "$dir/$module";

			self::loadClasspath( $module, $baseclass);
		}

	}


    private static function loadModuleBaseClass($module, $baseclass)
    {
        if(!$module) return;

        $module     = ucfirst($module);
        $classpath  = strtolower($module);

        self::trace("loading base classes for $module: " . $baseclass, E_USER_NOTICE);

        $defaults = array("{$module}Base", "{$module}Abstract", "{$module}_Base", "{$module}_Abstract");

        //try defaults
        if(!is_array($baseclass) && in_array($baseclass, $defaults))
        {
            foreach ($defaults as $class)
            {
                try
                {
                    $file = self::$app_root ."/$classpath/$class.php";
                    if( $class == $baseclass && file_exists($file)  ) include_once($file);

                } catch(Exception $e)
                {
                    self::trace("error loading base class: $file, error->". $e->getMessage());
                }

            }

            return;
        }

        //try given baseclass
        //make array... allows to autoload important classes in module first
        $bases = is_array($baseclass) ? $baseclass : array($baseclass);

        foreach ($bases as $base)
        {
            try
            {
                $file = self::$app_root . "/$classpath/$base.php";
                if (file_exists($file)) include_once($file);
            } catch (Exception $e)
            {
                self::trace("error loading base class: $file, error->" . $e->getMessage());
            }
        }

    }


    private static function loadClasspath($classpath, $baseclass)
    {
        self::loadModuleBaseClass($classpath, $baseclass);

        if( $files = self::readIncludeFolder($classpath, array('php'), array('')) )
        {
            foreach ($files as $file)
            {
                try
                {
                    if (file_exists($file)) include_once($file);

                } catch(Exception $e)
                {
                    self::trace("error loading base class: $file, error->". $e->getMessage() );
                }
            }
        }
    }


	private static function readIncludeFolder( $_f, array $_types = array("php"), $_arr = array('.htaccess'))
	{
		$_files = false;

		if( !file_exists( self::$app_root . "/$_f" ) ) return false;

		if( $_folder = opendir( self::$app_root . "/$_f") )
		{
			$_files = array();

			while ($_file = readdir($_folder))
			{
				$_ext = substr(strtolower($_file), strrpos($_file, ".") + 1);
				if (!in_array($_file, $_arr) && in_array($_ext, $_types))
				{
					$_files[] = self::$app_root . "/$_f/$_file" ;
				}
			}

            asort($_files);

            closedir($_folder);
        }

		return ($_files);
	}


    private static function trace($msg)
    {
        if(self::$verbose && class_exists('Error_Handler'))
        {
            Error_Handler::user("$msg");
        }
    }

}// END OF CLASS

?>