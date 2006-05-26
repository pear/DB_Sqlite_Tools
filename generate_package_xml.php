<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker */
// CVS: $Id$

require_once 'PEAR/PackageFileManager.php';

$pkg = new PEAR_PackageFileManager;

$packagedir = dirname(__FILE__);
$self = basename(__FILE__);

$desc = <<<EOT
DB_Sqlite_Tools is an object oriented interface to effectively manage and backup
Sqlite databases.It extends the existing functionality by providing a
comprehensive solution for database backup, live replication, export in XML
format, performance optmization and other functionalities like the insertion and
retrieval of encrypted data from an Sqlite database without any external
extension
EOT;

$notes = <<<EOT
* Fixed bug: print_r() with no return value
EOT;

$summary = <<<EOT
DB_Sqlite_Tools is an object oriented interface to effectively manage and backup Sqlite databases.
EOT;

$options = array(
    'simpleoutput'      => true,
    'doctype'           => 'D:\xSrv\www\include\pear\pear\data\PEAR\package.dtd',
    'package'           => 'DB_Sqlite_Tools',
    'license'           => 'BSD License',
    'baseinstalldir'    => '',
    'version'           => '0.1.5',
    'packagedirectory'  => $packagedir,
    'pathtopackagefile' => $packagedir,
    'state'             => 'alpha',
    'filelistgenerator' => 'cvs',
    'notes'             => $notes,
    'summary'           => $summary,
    'description'       => str_replace("\n", '', $desc),
    'dir_roles'         => array(
        'docs'          => 'doc',
        'data'          => 'data'
    ),
    'ignore'            => array(
        'package.xml',
        '*.tgz',
        $self,
        'tests/'
    )
);

$e = $pkg->setOptions($options);
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die;
}

// hack until they get their shit in line with docroot role
$pkg->addRole('tpl', 'php');
$pkg->addRole('png', 'php');
$pkg->addRole('gif', 'php');
$pkg->addRole('jpg', 'php');
$pkg->addRole('css', 'php');
$pkg->addRole('js', 'php');
$pkg->addRole('ini', 'php');
$pkg->addRole('inc', 'php');
$pkg->addRole('afm', 'php');
$pkg->addRole('pkg', 'doc');
$pkg->addRole('cls', 'doc');
$pkg->addRole('proc', 'doc');
$pkg->addRole('txt', 'doc');
$pkg->addRole('sh', 'script');

$pkg->addMaintainer('gurugeek', 'lead', 'David Costa', 'gurugeek@php.net');
$pkg->addMaintainer('morbid', 'constributor', 'Ashley Hewson', 'morbidness@gmail.com');
$pkg->addMaintainer('negora', 'constributor', 'Radu Negoescu', 'negora@dawnideas.com');
$pkg->addMaintainer('firman', 'lead', 'Firman Wandayandi', 'firman@php.net');

$pkg->addDependency('PHP', '5.0.0', 'ge', 'php');

$e = $pkg->addGlobalReplacement('package-info', '@package_version@', 'version');

$e = $pkg->writePackageFile();
if (PEAR::isError($e)) {
    echo $e->getMessage();
}

/*
 * Local variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>