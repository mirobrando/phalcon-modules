<?php

namespace mirolabs\phalcon\Framework;


use mirolabs\phalcon\Framework\Install\CreateFolders;

class Install
{

    public static function createProject()
    {
        CreateFolders::execute();
    }

} 