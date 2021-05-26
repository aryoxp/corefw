class Core {

  constructor(options = {}) {
    let def = {}
    this.settings = Object.assign({}, def, options);
    $('#core-config').data().each((k, v) => {
      this.config[k] = v;
    });
  }

  ins(options) {
    if (Core.instance && options)
      Core.instance.settings = Object.assign(Core.instance.settings, options);
    else Core.instance = new Core(options);
    return Core.instance;
  }

  ajax(options) {
    return Ajax.ins(options);
  }

}