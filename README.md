# .ini language file fixer

Suppose you have a lang. file as below in the format of CONSTANT="Value":

```
COM_ABC_TITLE="Title"
COM_ABC_TITLE_1="Title 1"
COM_ABC_TITLE="Title Blah"
COM_ABC_TITLE_2="Title"
```

### This PHP script does following:
- identifies unused constants
- comments out unused lang constants 
- identifies duplicate language constants eg: `COM_ABC_TITLE`
- identifies duplicate language values eg: `Title`

### Usage:

Identifies unused constants

```
php iniLanguageFilesFixer.php check-unused /path/to/valid/lang/file /path/to/valid/source/directory
```

[or] 

Comment out unused lang constants

```
php iniLanguageFilesFixer.php fix-unused /path/to/valid/lang/file /path/to/valid/source/directory
```

[or] 

Identifies duplicate language constants by constant name or value

```
php iniLanguageFilesFixer.php check-duplicates /path/to/valid/lang/file /path/to/valid/source/directory
```
