{extends 'base.tpl'}

{block 'content'}    
    {path action='Application.index' my = 2 x = 'AAA'}

    Hello, {$var}
{/block}