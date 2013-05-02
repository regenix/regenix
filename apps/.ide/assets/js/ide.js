IDE = {
    _assets: {},

    panels: {
        viewport: null,
        menu: null,
        bc: null,

        editor: null,
        leftSide: null,
        rightSide: null,
        statusBar: null
    },

    init: function(){
        this.panels.editor = new Ext.Panel({
            region: 'center',
            minHeight: 350,
            minWidth: 350,
            collapsible: false,
            split: true
        });

        this.panels.leftSide = new Ext.Panel({
            region: 'west',
            title: 'LeftSide',
            collapsible: true,
            width: 220,
            split: true,
            minWidth: 100,
            minHeight: 150
        });

        this.panels.leftSide.add(Ext.create('Ext.tree.Panel', {
            id: 'ide_tree',
            frame: false,
            rootVisible: false,
            root: {text: 'Root', expanded: true},
            border: false
        }));

        this.panels.statusBar = new Ext.Toolbar({
            region: 'south',
            height: 23,
            margin: '3 0 0 0'
        });

        this.panels.menu = Ext.create('Ext.toolbar.Toolbar', {
            region: 'north',
            height: 25,
            margin: '0 0 3 0',
            border: false
        });

        this.panels.viewport = new Ext.Viewport({
            layout: {
                type: 'border',
                padding: 3
            }
        });

        this.panels.viewport.add(this.panels.menu);
        this.panels.viewport.add(this.panels.editor);
        this.panels.viewport.add(this.panels.leftSide);
        this.panels.viewport.add(this.panels.statusBar);
    },

    /**
    * @param asset
    * @returns {boolean}
    * @param callback
    */
    loadAsset: function(asset, callback){
        if (this._assets[asset]){
            if (typeof callback === "function")
                callback();
            return true;
        }
        this._assets[asset] = true;
        var ext = asset.split('.').pop();

        switch (ext){
            case 'js': head.js(ASSETS_PATH + asset, callback); return;
            case 'css': head.load(ASSETS_PATH + asset, callback); return;
        }

        if (typeof callback === "function")
            callback();
    },

    /**
     * @param assets array
     * @param callback function
     */
    loadAssets: function(assets, callback){
        var result = [];
        for(var i in assets){
            var asset = assets[i];
            if (!this._assets[asset]){
                result.push(asset);
            }
        }
        var $this = this;
        async.each(result, function(asset, callback){
            $this.loadAsset(asset, callback);
        }, callback);
    }
}

var API = {
    _call: function(method, url, json){
        var url = ROOT + 'api/' + url;
        return $.ajax(url, {
            cache: false,
            contentType: "application/json",
            data: JSON.stringify(json),
            dataType: 'json',
            type: method
        });
    },

    call: function(url, json){
        return this._call('POST', url, json);
    },

    callGet: function(url, json){
        return this._call('GET', url, json);
    }
}

IDE.api = API;

Ext.require(['*']);
Ext.onReady(function(){
    IDE.init();
});