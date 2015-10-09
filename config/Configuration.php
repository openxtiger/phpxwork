<?php if(!defined('MS_XTIGER')) exit('Access Denied');
include_once(MS_XTIGER.'./config/Config.php');
/**
 * Created by openXtiger.org.
 * User: Administrator
 * Date: 2009-5-8
 * Time: 21:26:09
 */

class ConfigurationManager {
    private static $configurationInstance = NULL;
    static function getConfiguration() {
        if(self::$configurationInstance == NULL) {
            self::$configurationInstance = new Configuration();
            self::$configurationInstance->reload();
        }
        return self::$configurationInstance;
    }
}

class Configuration {
    var $packageContexts = array();
    var $runtimeConfiguration;
    var $runtimeProperties;
    public function reload() {
        $this->packageContexts = array();
        $p = new XmlConfigurationProvider();
        $p->init($this);

        $this->buildRuntimeConfiguration();
    }
    public function getRuntimeConfiguration() {
        return $this->runtimeConfiguration;   
    }
    public function getRuntimeProperties() {
        return $this->runtimeProperties;
    }
    public function addPackageConfig($name, $config) {
        return $this->packageContexts[$name] = $config;
    }
    public function getPackageConfig($name) {
        return $this->packageContexts[$name];
    }

    protected function buildRuntimeConfiguration() {
        $namespaceActionConfigs = array();
        $namespaceConfigs = array();
        
        foreach($this->packageContexts as $packageContext) {
            $namespace = $packageContext->namespace;
            $configs  = xt_hashmap($namespaceActionConfigs,$namespace);
            if(empty($configs)) {
                $configs = array('package_name'=>$packageContext->name);
            }
            $actionConfigs = $packageContext->getAllActionConfigs();
            foreach($actionConfigs as $ac) {
                $configs[$ac->name] = $this->buildFullActionConfig($packageContext,$ac);
            }
            
            $namespaceActionConfigs[$namespace] = $configs;
            /*$defaultAction =  $packageContext->getFullDefaultActionRef();
            if (!empty($defaultAction)) {
                $namespaceConfigs[$namespace] = $defaultAction;
            }*/
            $defaultInterceptor = _xtc_constructInterceptors($packageContext,$packageContext->getFullDefaultInterceptorRef(),array());
            if( count($defaultInterceptor)> 0 ) {
                $namespaceConfigs[$namespace] = $this->buildFullInterceptorsConfig($defaultInterceptor);
            }
        }
        
        $this->runtimeConfiguration = '$namespaceActionConfigs='.preg_replace("/'(MS_.*?)\//","\\1.'/",xt_implode($namespaceActionConfigs)).";\n";
        $this->runtimeConfiguration .='$namespaceConfigs='.preg_replace("/'(MS_.*?)\//","\\1.'",xt_implode($namespaceConfigs)).';';
        
        //$this->runtimeProperties 
    }

    protected function  buildFullActionConfig($packageContext, $baseConfig) {
        $config = array(); 
        $config['file'] = $baseConfig->file;
        $config['results'] = $baseConfig->results;
        $config['interceptors'] = $this->buildFullInterceptorsConfig($baseConfig->interceptors);
        return $config;    
    }
    protected function  buildFullInterceptorsConfig($interceptors) {
        $r = array();
        $files = array();
        foreach($interceptors as $is) {
            $files[] = $is->file;
            if(count($is->params)>0) {
                $r[] = array(/*'name'=>$is->name,'package'=>$is->package,*/'method'=>$is->package.'_'.$is->name.'_'.$is->method,'params'=>$is->params);
            } else {
                $r[] = array(/*'name'=>$is->name,'package'=>$is->package,*/'method'=>$is->package.'_'.$is->name.'_'.$is->method);
            }
        }
        $files = array_unique($files);
        if(count($files)>0)
            $r['files'] = count($files)==1 ? $files[0] : $files;
        return $r;     
    }
}

class PackageConfig {
    var $parents;
    var $name,$namespace;
    var $resultTypeConfigs;
    var $defaultResultType;
    var $interceptorConfigs;
    var $defaultInterceptorRef;
    var $actionConfigs;
    var $defaultActionRef;

    function PackageConfig($name,$namespace,$parents) {
        $this->name = $name;
        $this->namespace =  $namespace;
        $this->parents = $parents;

        $this->resultTypeConfigs = array();
        $this->interceptorConfigs = array();
        $this->actionConfigs = array();
    }
    public function setDefaultResultType($type) {
        $this->defaultResultType = $type;
    }
    public function addResultTypeConfig($config) {
        $this->resultTypeConfigs[$config->name] = $config;
    }
    public function addInterceptorConfig($config) {
        $this->interceptorConfigs[$config->name] = $config;
    }
    public function addInterceptorStackConfig($config) {
        $this->interceptorConfigs[$config->name] = $config;
    }
    public function addActionConfig($config) {
        $this->actionConfigs[$config->name] = $config;
    }
    public function getAllInterceptorConfigs() {
        $retMap = array();
        if(count($this->parents) >0 ) {
            foreach($this->parents as $parent){
                $r = $parent->getAllInterceptorConfigs();
                if(is_array($r) && count($r)>0 )
                    $retMap = array_merge($retMap, $r);
            }
        }
        $retMap = array_merge($retMap,$this->interceptorConfigs);
        return $retMap;
    }

    public function getAllResultTypeConfigs() {
        $retMap = array();
        if(count($this->parents) >0 ) {
            foreach($this->parents as $parent){
                $r = $parent->getAllResultTypeConfigs();
                if(is_array($r) && count($r)>0 )
                    $retMap = array_merge($retMap, $r);
            }
        }
        $retMap = array_merge($retMap,$this->resultTypeConfigs);
        return $retMap;
    }

    public function getAllActionConfigs() {
        $retMap = array();
        if(count($this->parents) >0 ) {
            foreach($this->parents as $parent){
                $r = $parent->getAllActionConfigs();
                if(is_array($r) && count($r)>0 )
                    $retMap = array_merge($retMap, $r);
            }
        }
        $retMap = array_merge($retMap,$this->actionConfigs);
        return $retMap;
    }
    
    public function getFullDefaultResultType() {
        if ( empty($this->defaultResultType) && count($this->parents) >0 ) {
             foreach($this->parents as $parent){
                $parentDefault = $parent->getFullDefaultResultType();
                if(!empty($parentDefault))
                    return $parentDefault;
            }
        }
        return $this->defaultResultType;
    }

    public function getFullDefaultInterceptorRef() {
        if (empty($this->defaultInterceptorRef) && count($this->parents) >0 ) {
            foreach($this->parents as $parent){
                $parentDefault = $parent->getFullDefaultInterceptorRef();
                if(!empty($parentDefault))
                    return $parentDefault;
            }
        }

        return $this->defaultInterceptorRef;
    }

    public function getFullDefaultActionRef() {
        if ( empty($this->defaultActionRef) && count($this->parents) > 0 ) {
             foreach($this->parents as $parent){
                $parentDefault = $parent->getFullDefaultActionRef();
                if(!empty($parentDefault))
                    return $parentDefault;
            }
        }
        return $this->defaultActionRef;
    }

    public function setDefaultInterceptorRef($name) {
        $this->defaultInterceptorRef = $name;
    }

    public function setDefaultActionRef($name) {
        $this->defaultActionRef = $name;
    }

}

class ResultTypeConfig {
    var $params,$defaultResultParam;
    var $name,$file,$package,$method;
    function ResultTypeConfig($name,$file,$package,$method,$defaultResultParam) {
        $this->params = array();
        $this->name = $name;
        $this->file = $file;
        $this->package = $package;
        $this->method = $method;
        $this->defaultResultParam = $defaultResultParam;
    }
    function setParams($params) {
        $this->params = $params;
    }
}

class InterceptorConfig {
    var $params;
    var $name,$package,$method,$file;
    function InterceptorConfig($name,$package,$method,$file,$params) {
        $this->name = $name;
        $this->package = $package;
        $this->method = $method;
        $this->file = $file;
        $this->params = $params;
    }
    function  mergeParams($params){
        if(is_array($params) && count($params)>0 )
            $this->params = array_merge($this->params, $params);     
    }
}
class InterceptorStackConfig {
    var $name;
    var $interceptorConfigs;

    function InterceptorStackConfig($name){
        $this->name = $name;
        $this->interceptorConfigs = array();
    }
    function addInterceptorConfig($interceptorConfig){
        $this->interceptorConfigs[] = $interceptorConfig;
    }
    function addInterceptorConfigs($interceptorConfigs) {
        if(is_array($interceptorConfigs) && count($interceptorConfigs))
            $this->interceptorConfigs = array_merge($this->interceptorConfigs,$interceptorConfigs);
    }
    function getInterceptorConfigs() {
        return $this->interceptorConfigs;
    }
    function  mergeParams($params){
       foreach($this->interceptorConfigs as $config){
            $config->mergeParams($params); 
       }
    }
}

class ActionConfig {
    var $name,$file,$results,$interceptors,$params,$package;
    function ActionConfig($name,$file,$results,$interceptors,$params,$package) {
        $this->name = $name;
        $this->file= $file;
        $this->results = $results;
        $this->interceptors = $interceptors;
        $this->params= $params;
        $this->package = $package;
    }
}

class ResultConfig {
    var $name,$package,$method,$file,$params;
    function ResultConfig($name,$package,$method, $file, $params) {
        $this->name = $name;
        $this->package = $package;
        $this->method = $method;
        $this->file = $file;
        $this->params = $params;
    }
    
}
class XmlConfigurationProvider {
    var $configuration;
    var $configFileName;
    
    private static $includedFileNames   = array();
    
    function XmlConfigurationProvider() {
        $this->configFileName = MS_APPPATH.'./xtiger.xml';
    }
    
    /*function __construct() {
        $this->XmlConfigurationProvider();
    }
    function __destruct() {

    }*/

    function init($configuration) {
        $this->configuration  = $configuration;
        $this->loadConfigurationFile($this->configFileName); 
    }
    public function loadConfigurationFile($configFileName) {
        if(in_array($configFileName,self::$includedFileNames)) return;
        self::$includedFileNames[] = $configFileName;

        if(!file_exists($configFileName)) return;
        $content = xt_readfile($configFileName,'xml');
        
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadXML($content);
        $rootElement = $xmlDoc->documentElement;
        foreach ($rootElement->childNodes as $child) {
            if($child->nodeName=='package'){
                $this->addPackage($child);
            } else if($child->nodeName=='include') {
                $this->loadConfigurationFile(_xtc_replace_constant($child->getAttribute('file')));
            }
        }
    }
    protected function addPackage($packageElement) {
        //buildPackageContext
        $name = $packageElement->getAttribute('name');
        $extends = $packageElement->getAttribute('extends');
        $parent = array();

        if(!empty($extends)){
            $extends = explode(",",$extends);
            foreach($extends as $value){
                $parent[] = $this->configuration->getPackageConfig($value);
            }
        }
        
        $package = new PackageConfig($name, $packageElement->getAttribute('namespace'),$parent);

        // add result types (and default result) to this package
        $this->addResultTypes($package, $packageElement);

        // load the interceptors and interceptor stacks for this package
        $this->loadInterceptors($package, $packageElement);

        // load the default interceptor reference for this package
        $this->loadDefaultInterceptorRef($package, $packageElement);

        /*// load the global result list for this package
        loadGlobalResults($package, $packageElement);*/

        /*// load the global exception handler list for this package
        loadGobalExceptionMappings($package, $packageElement);*/

        $actionList = $packageElement->getElementsByTagName("action");
        foreach ($actionList as $child) {
            $this->addAction($child, $package);
        }

        // load the default action reference for this package
        //$this->loadDefaultActionRef($package, $packageElement);
        
        $this->configuration->addPackageConfig($name, $package);
    }
    
    protected function addResultTypes($packageContext, $element) {
        $resultTypeList = $element->getElementsByTagName("result-type");
        foreach ($resultTypeList as $child) {
            $name = $child->getAttribute('name');
            $resultType = new ResultTypeConfig($name,$child->getAttribute('file')
                                                    ,$child->getAttribute('package'),$child->getAttribute('method'),
                                                    'location');
                                                    
            $packageContext->addResultTypeConfig($resultType);

            $resultType->setParams(_xtc_getParams($child));

            if($child->getAttribute('default') === 'true'){
                $packageContext->setDefaultResultType($name);
            }
        }
    }
    protected function loadInterceptors($packageContext, $element) {
        $interceptorsList = $element->getElementsByTagName("interceptors");
        foreach ($interceptorsList as $item) {
            $file = $item->getAttribute('file');
            $interceptors = $item->getElementsByTagName('interceptor');
            foreach ($interceptors as $child) {
                $config = new InterceptorConfig($child->getAttribute('name'),$child->getAttribute('package'),
                                                 $child->getAttribute('method'),$file,_xtc_getParams($child));
                $packageContext->addInterceptorConfig($config);
            }
        }
        $this->loadInterceptorStacks($element, $packageContext);
    }

    protected function loadInterceptorStacks($element, $context) {
        $interceptorStackList = $element->getElementsByTagName("interceptor-stack");
        foreach ($interceptorStackList as $child) {
            $context->addInterceptorStackConfig($this->loadInterceptorStack($child,$context));
        }
    }
    
    protected function loadInterceptorStack($element, $context) {
        $name = $element->getAttribute('name');
        $config = new InterceptorStackConfig($name);
        $interceptorRefList = $element->getElementsByTagName("interceptor-ref");
        foreach ($interceptorRefList as $child) {
            $config->addInterceptorConfigs($this->lookupInterceptorReference($context,$child));   
        }
        return $config;
    }

    protected function lookupInterceptorReference($context, $element) {
        return _xtc_constructInterceptors($context,$element->getAttribute("name"),_xtc_getParams($element));
    }

    protected function loadDefaultInterceptorRef($packageContext, $element) {
        $resultTypeList = $element->getElementsByTagName("default-interceptor-ref");
        foreach ($resultTypeList as $child) {
            $packageContext->setDefaultInterceptorRef($child->getAttribute("name"));
            break;   
        }
    }
    protected function addAction($actionElement,$packageContext) {
        $actionParams =  _xtc_getParams($actionElement);

        $results = $this->buildResults($actionElement, $packageContext);

        $interceptorList =  $this->buildInterceptorList($actionElement, $packageContext);

        $packageContext->addActionConfig(new ActionConfig(
             $actionElement->getAttribute("name"),
             $actionElement->getAttribute("file"),
             $results,$interceptorList,$actionParams,$packageContext->name  
        ));
    }

     protected function buildResults($element,$packageContext) {
         $resultEls = $element->getElementsByTagName("result");
         $results = array();
         foreach ($resultEls as $resultElement) {
            $resultType = $resultElement->getAttribute("type");
            $resultName = $resultElement->getAttribute("name");
            if(empty($resultType)) {
                $resultType = $packageContext->getFullDefaultResultType();
                if(empty($resultType)) {
                    return NULL;
                }
            }
            $config = $packageContext->getAllResultTypeConfigs();
            $config = $config[$resultType];
            $resultParams = _xtc_getParams($resultElement);
            if(count($resultParams) == 0 ) {
                $resultParams[$config->defaultResultParam] = $resultElement->nodeValue;
            }
            $resultParams = array_merge($resultParams,$config->params);
            $results[$resultName] = new ResultConfig($config->name,$config->package,$config->method,$config->file, $resultParams);
         }
         return $results;
     }

    protected function buildInterceptorList($element,$packageContext) {
        $results = array();
        $interceptorRefList = $element->getElementsByTagName("interceptor-ref");
        foreach ($interceptorRefList as $child) {
            $interceptors = $this->lookupInterceptorReference($packageContext, $child);
            if(is_array($interceptors) && count($interceptors)) {
                $results = array_merge($results, $interceptors);   
            }
        }
        return $results;
    }
    /*protected function loadDefaultActionRef($packageContext,$element) {
        $defaultActionList = $element->getElementsByTagName("default-action-ref");
        foreach ($defaultActionList as $child) {
            $packageContext->setDefaultActionRef($child->getAttribute("name"));
        }
    }*/
}