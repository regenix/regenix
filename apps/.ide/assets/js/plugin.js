Plugin = function(){}

Plugin.prototype = {

    name: '',
    assets: [],
    code: '',
    clazz: '',

    constructor: function(meta){
        if (meta){
            this.loadMetaFromJson(meta);
        }
    },

    loadMetaFromJson: function(json){
        this.name   = json.name;
        this.assets = json['all_assets'];
        this.code   = json.code;
        this.clazz  = json.class;
    },

    register: function(callback){
        var $this = this;
        async.parallel([
            function(callback){
                IDE.loadAssets($this.assets, callback);
            }
        ], callback);
    }
}