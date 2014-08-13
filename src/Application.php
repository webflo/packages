<?php

namespace Terramar\Packages;

use Nice\Application as BaseApplication;
use Nice\Extension\SecurityExtension;
use Nice\Extension\SessionExtension;
use Nice\Extension\TwigExtension;
use Symfony\Component\Yaml\Yaml;
use Terramar\Packages\DependencyInjection\Compiler\TwigExtensionPass;
use Terramar\Packages\DependencyInjection\DoctrineOrmExtension;
use Terramar\Packages\DependencyInjection\PackagesExtension;
use Terramar\Packages\Plugin\CloneProject\Plugin as CloneProjectPlugin;
use Terramar\Packages\Plugin\Git\Plugin as GitPlugin;
use Terramar\Packages\Plugin\GitLab\Plugin as GitLabPlugin;
use Terramar\Packages\Plugin\Sami\Plugin as SamiPlugin;
use Terramar\Packages\Plugin\Satis\Plugin as SatisPlugin;
use Terramar\Packages\Plugin\PluginInterface;

class Application extends BaseApplication
{
    /**
     * @var array|PluginInterface[]
     */
    private $plugins = array();

    /**
     * Register default extensions
     */
    protected function registerDefaultExtensions()
    {
        parent::registerDefaultExtensions();

        $this->registerDefaultPlugins();

        $config = Yaml::parse(file_get_contents($this->getRootDir() . '/config.yml'));
        $security = isset($config['security']) ? $config['security'] : array();
        $doctrine = isset($config['doctrine']) ? $config['doctrine'] : array();
        $resque = isset($config['resque']) ? $config['resque'] : null;

        $this->appendExtension(new PackagesExtension($this->plugins, array(
                'output_dir' => $this->getRootDir() . '/web',
                'resque' => $resque
            )));
        $this->appendExtension(new DoctrineOrmExtension($doctrine));
        $this->appendExtension(new SessionExtension());
        $this->appendExtension(new TwigExtension($this->getRootDir() . '/views'));
        $this->appendExtension(new SecurityExtension(array(
                'username' => isset($security['username']) ? $security['username'] : null,
                'password' => isset($security['password']) ? $security['password'] : null,
                'firewall' => '^/manage',
                'success_path' => '/manage'
            )));

        $this->addCompilerPass(new TwigExtensionPass());
    }

    /**
     * Register default plugins
     */
    protected function registerDefaultPlugins()
    {
        $this->registerPlugin(new GitPlugin());
        $this->registerPlugin(new GitLabPlugin());
        $this->registerPlugin(new CloneProjectPlugin());
        $this->registerPlugin(new SamiPlugin());
        $this->registerPlugin(new SatisPlugin());
    }

    /**
     * Register a Plugin with the Application
     *
     * @param PluginInterface $plugin
     */
    public function registerPlugin(PluginInterface $plugin)
    {
        $this->plugins[$plugin->getName()] = $plugin;
    }
}
