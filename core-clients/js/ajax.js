class Ajax {
  constructor(options = {}) {
    this.def = {
      method: 'get',
      delay: 10000,
      useDefaultRejection: true,
      holdError: false,
      timeout: 30000,
      compressed: false,
      baseUrl: "/"
    }
    this.settings = Object.assign(this.def, options);

    this.onReject = this.defaultReject;
    this.onResolve = this.defaultResolve;
  }

  static ins(options) {
    if (Ajax.instance && options)
      Ajax.instance.settings = Object.assign(Ajax.instance.settings, options);
    else Ajax.instance = new Ajax(options);
    return Ajax.instance;
  }

  defaultResolve(response) {
    console.info(response);
  }

  defaultReject(error) {
    console.error(error);
  }

  get(url, data, options) {
    this.send('get', url, data, options);
    return this;
  }

  post(url, data, options) {
    this.send('post', url, data, options);
    return this;
  }

  send(method, url, data, options) {
    this.onReject = this.defaultReject;
    
    let requestSettings = Object.assign({}, this.settings, this.def, options);

    if (this.settings.baseUrl.trim().substr(-1) != '/')
    this.settings.baseUrl += '/';

    let requestUrl = url.toLowerCase().startsWith('http') ? url : this.requestSettings.baseUrl + "/" + url;

    this.requestPromise = new Promise((resolve, reject) => {
      $.ajax({
        url: requestUrl,
        method: method,
        data: data,
        timeout: requestSettings.timeout
      }).done((response) => {
        this.response = response;
        if (!response.status) {
          reject(response.error ? response.error : response)
        } else {
          if (requestSettings.compressed) {
            const charData = atob(response.result).split('').map(x => {
              return x.charCodeAt(0);
            });
            const inflated = JSON.parse(pako.inflate(new Uint8Array(charData), {
              to: 'string'
            }));
            resolve(inflated);
          } else resolve(response.result);
        }
        return;
      }).fail((response) => {
        if (!response.status && response.error) 
          reject(response.error);
        else reject(response.responseText);
      })
    });
    
    this.requestPromise.catch(this.defaultReject);
    this.requestPromise.catch(this.onReject);
    this.requestPromise.then(this.onResolve);

    return this;
  }

  then(onResolve, onReject) {
    this.onResolve = onResolve;
    if (onReject) this.onReject = onReject;
    return this;
  }

  catch(onReject) {
    this.onReject = onReject;
    return this;
  }

  finally(onFinally) {
    this.onFinally = onFinally;
    return this;
  }

  // onReject(error) {
  //   if (this.gui) this.gui.notify(error, {
  //     type: 'danger',
  //     delay: this.settings.delay
  //   });
  // }

  // holdError() {
  //   this.settings.delay = 0;
  //   return this;
  // }

  // hold() {
  //   this.holdError()
  // }

  // post(url, data, options) {
  //   this.settings.method = 'post'
  //   return this.send(url, data, options)
  // }

  // get(url, data, options) {
  //   this.settings.method = 'get'
  //   return this.send(url, data, options)
  // }

  // send(url, data, options) {

  //   let currentSettings = Object.assign({}, this.settings, this.def, options);

  //   if (!arguments.length) return;
  //   let u = undefined;
  //   let d = undefined;
  //   if (typeof arguments[0] == 'object') {
  //     u = arguments[0].url;
  //     d = arguments[0].data ? arguments[0].data : undefined
  //   } else u = url;
  //   d = data ? data : d;

  //   u = u.toLowerCase().startsWith('http') ?
  //     u : ($('#core-config').length ? $('#core-config').attr("data-base-url") + u :
  //       u);

  //   this.promise = new Promise((resolve, reject) => {
  //     $.ajax({
  //       url: u,
  //       method: currentSettings.method,
  //       data: d,
  //       timeout: currentSettings.timeout
  //     }).done((response) => {
  //       if (!response.status) {
  //         console.error(response);
  //         reject(response.error ? response.error : response)
  //       } else {
  //         if (currentSettings.compressed) {
  //           const charData = atob(response.result).split('').map(x => {
  //             return x.charCodeAt(0);
  //           });
  //           const inflated = JSON.parse(pako.inflate(new Uint8Array(charData), {
  //             to: 'string'
  //           }));
  //           resolve(inflated);
  //         } else resolve(response.result);
  //       }
  //       return;
  //     }).fail((response) => {
  //       console.error(response);
  //       if (!response.status) reject(response.error ? response.error : response.responseText);
  //       else reject(response.responseText);
  //     })
  //   });
  //   return this.settings.holdError ? this.holdError() : this;
  // }
  // then(onResolve, onReject) {
  //   if (this.settings.useDefaultRejection)
  //     this.promise.then(onResolve, onReject).catch(this.onReject.bind(this));
  //   else this.promise.then(onResolve, onReject)
  //   return this;
  // }
  // finally(onFinally) {
  //   if (typeof onFinally == "function") this.promise.finally(onFinally);
  //   return this;
  // } catch (onReject) {
  //   this.promise.catch(onReject);
  //   return this;
  // }
}