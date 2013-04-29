IDE = {
    _assets: {},

    panels: {
        menu: null,
        bc: null,
        status: null,
        editor: null,
        files: null
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

$(function(){
    IDE.panels.menu = $('.panel_menu');
    IDE.panels.bc   = $('.panel_bc');
    IDE.panels.editor = $('.panel_editor');
    IDE.panels.files  = $('.panel_files');
    IDE.panels.status = $('.panel_status');
});