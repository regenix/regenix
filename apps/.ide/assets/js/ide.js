function c(idCmp){
    return IDE.c(idCmp);
}

IDE = {
    _assets: {},
    _ready: [],

    _plugins: [],

    init: function(){
        var $this = this;
        IDE.api.call('core/plugins').success(function(json){
            for(var i in json.data){
                var meta = json.data[i];
                var plugin = new Plugin();
                plugin.loadMetaFromJson(meta);
                $this._plugins.push(plugin);
            }
            $this.__loadPlugins(function(){
                $this.__callOnReady();
                $this.setStatusLoading('Loading done.', true);
                setTimeout(function(){
                    $('.preloading').fadeOut();
                }, 200);
            });
        });
    },

    c: function(idCmp){
        return Ext.getCmp(idCmp);
    },

    on: function(idCmp, event, callback){
        return Ext.getCmp(idCmp).on(event, callback);
    },

    setStatusLoading: function(status, noPoints){
        $('.preloading .status').html(status + (noPoints ? '' : ' ...'));
    },

    addComponent: function(cmp){
        this._viewport.add(cmp);
    },

    onReady: function(callback){
        this._ready.push(callback);
    },

    __callOnReady: function(){
        // callback on ready
        for(var i in this._ready){
            this._ready[i]();
        }
    },

    __loadPlugins: function(callback){
        var $this = this;
        async.each(this._plugins, function(plugin, callback){
            $this.setStatusLoading('Load plugin: ' + plugin.name);
            plugin.register(callback);
        }, callback);
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
        var tmp = asset.split('.');
        var prefix = tmp[tmp.length - 2];

        switch (ext){
            case 'js': this.setStatusLoading('Load asset: '+asset); head.js(ASSETS_PATH + asset, callback); return;
            case 'css': this.setStatusLoading('Load asset: '+asset); head.load(ASSETS_PATH + asset, callback); return;
            case 'json': {
                this.setStatusLoading('Load asset: '+asset);
                if (prefix === 'ext'){
                    return $.ajax(ASSETS_PATH + asset,{
                        cache: false,
                        type: 'GET',
                        dataType: 'json'
                    }).success(function(json){
                        for(var x in json){
                            var item = json[x];
                            var data = item[1];
                            var cmp = Ext.create(item[0], data);

                            if (data['parent']){
                                var parent = Ext.getCmp(data['parent']);
                                parent.add(cmp);
                            }
                        }
                        callback();
                    });
                }
            }
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
    __onError: function(result, method, url, json){
        console.log('error: ' + method + ':' + url);
    },

    _call: function(method, url, json){
        var $url  = url;
        var $this = this;
        var url = ROOT + 'api/' + url;
        return $.ajax(url, {
            cache: false,
            contentType: "application/json",
            data: JSON.stringify(json),
            dataType: 'json',
            type: method
        }).error(function(result){
            $this.__onError(result, method, $url, json);
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