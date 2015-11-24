<?php


$source=new PHPSource(__DIR__.'/../../plugins/test/nuredenumi/Plugin5.plugin.php');

$declaredClasses=$source->getDeclaredClasses();



$this->ASSERT_TRUE(isset($declaredClasses['\\test\nuredenumi\Plugin5']));
$class=$declaredClasses['\\test\nuredenumi\Plugin5'];
$this->ASSERT_EQUALS('Plugin5', $class['name']);

$this->ASSERT_EQUALS('\Base', $class[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME]);


$this->ASSERT_TRUE(isset($declaredClasses['\\test\nuredenumi\Plugin5Functionality']));
$class=$declaredClasses['\test\nuredenumi\Plugin5Functionality'];
$this->ASSERT_EQUALS('Plugin5Functionality', $class['name']);

$this->ASSERT_EQUALS('\test\nuredenumi\Plugin5', $class[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME]);

//$this->ASSERT_EQUALS('', $class['']);
//$this->ASSERT_EQUALS('', $class['']);

$source=new PHPSource(__DIR__.'/../../plugins/test/nsname/Plugin6.plugin.php');
$declaredClasses=$source->getDeclaredClasses();
print_r($declaredClasses);

$aliases=$source->getClassesAliases();
print_r($aliases);