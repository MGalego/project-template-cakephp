<?php

namespace App\Shell;

use App\Utility\Model;
use BadMethodCallException;
use CakeDC\Users\Shell\UsersShell as BaseShell;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use CsvMigrations\Controller\Traits\PanelsTrait;
use CsvMigrations\Exception\UnsupportedPrimaryKeyException;
use CsvMigrations\FieldHandlers\FieldHandlerFactory;
use CsvMigrations\Utility as CsvUtility;
use CsvMigrations\Utility\Panel;
use CsvMigrations\Utility\Validate\Utility;
use PHPUnit\Framework\Assert;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\ModuleConfig;
use Qobo\Utils\ModuleConfig\Parser\Parser;

class GenerateLanguageFilesShell extends BaseShell
{
    use PanelsTrait;

    /**
     * @var array $modules List of known modules
     */
    protected $modules;
    /**
     * @var string
     */
    private $module;

    /**
     * Set shell description and command line options
     *
     * @return ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = new ConsoleOptionParser('console');
        $parser->setDescription('Generating .ctp files for language translation');
        $parser->addOption('module', [
            'short' => 'm',
            'help' => 'Specific module to fix',
        ]);

        return $parser;
    }

    /**
     * Main method for shell execution
     *
     * @param string $modules Comma-separated list of module names to validate
     * @return bool|int|void
     */
    public function main(string $modules = '')
    {
        $this->setModule();
        $this->modules = !empty($this->getModule()) ? (array)$this->getModule() : Utility::getModules();

        if (empty($this->modules)) {
            $this->warn('Did not find any modules');

            return false;
        }

        $modules = '' === $modules ? $this->modules : explode(',', $modules);

        foreach ($modules as $module) {
            $this->generateCtpFiles((string)$module);
        }
    }

    /**
     * Generate file for each module.
     * @param string $module Module name
     * @return void
     */
    public function generateCtpFiles(string $module): void
    {
        $factory = new FieldHandlerFactory();
        $table = TableRegistry::getTableLocator()->get($module);

        $callable = [$this, 'generateCtpLine'];
        if (!is_callable($callable)) {
            throw new BadMethodCallException(
                sprintf("Method %s.%s does not exist", get_class($callable[0]), $callable[1])
            );
        }

        $ctpLines = "<?php\n\n";

        //Module Title
        $ctpLines .= "//Module: " . $module . ", Module Title: " . $module . "\n";
        $ctpLines .= $this->generateCtpLine(Inflector::humanize(Inflector::underscore($module))) . "\n\n";

        //Module Alias
        $ctpLines .= "//Module: " . $module . ", Module Alias: " . $table->getAlias() . "\n";
        $ctpLines .= $this->generateCtpLine($table->getAlias()) . "\n\n";

        //Field Labels
        $fieldLabelConfig = (new ModuleConfig(ConfigType::FIELDS(), $module))->parseToArray();
        foreach ($fieldLabelConfig as $key => $fieldLabel) {
            if (isset($fieldLabel['label'])) {
                $ctpLines .= "//Module: " . $module . ", Field Label for: " . $key . "\n";
                $label = $factory->renderName($module, $fieldLabel['label']);
                $ctpLines .= $this->generateCtpLine($label) . "\n\n";
            }
        }

        //Migration Field Labels
        $migrationFieldLabels = $this->migrationFieldLabels($module);
        if (is_array($migrationFieldLabels) && 0 < count($migrationFieldLabels)) {
            $ctpLinesArray = [];
            $ctpLinesArray = array_map([$this, "generateCtpLine"], $migrationFieldLabels);
            $ctpLines .= "//Module: " . $module . ", Migration Field Labels" . "\n";
            $ctpLines .= implode("\n", $ctpLinesArray);
            $ctpLines .= "\n\n";
        }

        //Menu Labels
        $menuLabelConfig = (new ModuleConfig(ConfigType::MENUS(), $module))->parseToArray();
        if (is_array($menuLabelConfig) && 0 < count($menuLabelConfig)) {
            $menuItems = $this->translateMenuItems(array_shift($menuLabelConfig));
            //var_dump($menuItems);
            $ctpLinesArray = [];
            $ctpLinesArray = array_map([$this, "generateCtpLine"], $menuItems);
            $ctpLines .= "//Module: " . $module . ", Menus Labels" . "\n";
            $ctpLines .= implode("\n", $ctpLinesArray);
            $ctpLines .= "\n\n";
        }

        //Check for Panel Titles
        $hasViews = false;
        $views = ['view', 'edit', 'add'];
        foreach ($views as $view) {
            $panels = CsvUtility\Field::getCsvView($table, $view, true, true);

            if (empty($panels)) {
                continue;
            }

            $hasViews = true;
            $ctpLines .= "//Module: " . $module . ", CsvView: " . $view . "\n";
            $actionTitles = array_keys($panels);

            $ctpLinesArray = [];
            $ctpLinesArray = array_map([$this, "generateCtpLine"], $actionTitles);
            $ctpLines .= implode("\n", $ctpLinesArray);
            $ctpLines .= "\n\n";
        }

        /**
         * @var string $filename
         */
        $filename = 'src/Template/Module/Translations/' . $module . ".ctp";
        if (!file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }

        Assert::assertIsWritable(dirname($filename), (string)__("Unable to open file {0}.ctp", $module));
        if (false !== $ctpFile = fopen($filename, "w")) {
            $ctpContent = $ctpLines . "\n";
            fwrite($ctpFile, $ctpContent);
            fclose($ctpFile);
        }
    }

    /**
     * Check fields and their types.
     * @param string $module Module name
     * @return mixed[]
     */
    private function migrationFieldLabels(string $module): array
    {
        $mc = $this->getModuleConfig($module, []);

        $config = json_encode($mc->parse());
        $fields = $mc->parseToArray();

        $fieldLabels = [];
        $factory = new FieldHandlerFactory();
        foreach ($fields as $field) {
            // $label = $factory->renderName($module, $field['name']);
            // $fieldLabels[] = $label;
            $fieldLabels[] = Inflector::humanize(Inflector::underscore($field['name']));
        }

        return $fieldLabels;
    }

    /**
     * Creates a custom instance of `ModuleConfig` with a parser, schema and
     * extra validation.
     *
     * @param string $module Module.
     * @param string[] $options Options.
     * @return ModuleConfig Module Config.
     */
    protected function getModuleConfig(string $module, array $options = []): ModuleConfig
    {
        $configFile = empty($options['configFile']) ? null : $options['configFile'];
        $mc = new ModuleConfig(ConfigType::MIGRATION(), $module, $configFile, ['cacheSkip' => true]);

        $schema = $mc->createSchema(['lint' => true]);
        $mc->setParser(new Parser($schema, ['lint' => true, 'validate' => true]));

        return $mc;
    }

    /**
     * Method for generating translation line
     * @param  string $item Text for translation
     * @return string
     */
    private function generateCtpLine(string $item): string
    {
        return "echo __('" . $item . "');";
    }

    /**
     * Method for generating translation lines fro menus
     * @param  mixed[] $menu Text for translation
     * @return mixed[]
     */
    private function translateMenuItems(array $menu): array
    {
        $menuItems = [];

        foreach ($menu as $key => $menuItem) {
            if (isset($menuItem['label'])) {
                $menuItems[] = $menuItem['label'];
            }
            if (isset($menuItem['desc'])) {
                $menuItems[] = $menuItem['desc'];
            }

            if (isset($menuItem['children']) && is_array($menuItem['children']) && 0 < count($menuItem['children'])) {
                $childrenItems = $this->translateMenuItems($menuItem['children']);
                $menuItems = array_merge($menuItems, $childrenItems);
            }
        }

        return $menuItems;
    }

    /**
     * @param string $module Module Name
     * @return void|null|int
     */
    public function setModule(string $module = '')
    {
        if (isset($this->params['module'])) {
            $this->module = $this->params['module'];
        } else {
            $this->module = $module;
        }
    }

    /**
     * @return string
     */
    private function getModule(): string
    {
        return $this->module;
    }
}
