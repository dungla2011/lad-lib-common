<?php

namespace LadLib\Common\Database;

/**
 * This base class will be inherited in Model class, to CURD data
 * Using: Object maps properties with Fields of a row in a table of DB
 * Each property is a field in table of DB
 * Each object store data of a raw of table
 */
interface IBaseDb
{
    function getDbName();
    function getTableName();
}

