(()=>{var t={434:()=>{},444:(t,e,n)=>{var s=n(434);s.__esModule&&(s=s.default),"string"==typeof s&&(s=[[t.id,s,""]]),s.locals&&(t.exports=s.locals),(0,n(159).Z)("e656a1c0",s,!1,{})},159:(t,e,n)=>{"use strict";function s(t,e){for(var n=[],s={},o=0;o<e.length;o++){var c=e[o],i=c[0],a={id:t+":"+o,css:c[1],media:c[2],sourceMap:c[3]};s[i]?s[i].parts.push(a):n.push(s[i]={id:i,parts:[a]})}return n}n.d(e,{Z:()=>p});var o="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!o)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var c={},i=o&&(document.head||document.getElementsByTagName("head")[0]),a=null,r=0,d=!1,u=function(){},l=null,m="data-vue-ssr-id",h="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function p(t,e,n,o){d=n,l=o||{};var i=s(t,e);return f(i),function(e){for(var n=[],o=0;o<i.length;o++){var a=i[o];(r=c[a.id]).refs--,n.push(r)}for(e?f(i=s(t,e)):i=[],o=0;o<n.length;o++){var r;if(0===(r=n[o]).refs){for(var d=0;d<r.parts.length;d++)r.parts[d]();delete c[r.id]}}}}function f(t){for(var e=0;e<t.length;e++){var n=t[e],s=c[n.id];if(s){s.refs++;for(var o=0;o<s.parts.length;o++)s.parts[o](n.parts[o]);for(;o<n.parts.length;o++)s.parts.push(b(n.parts[o]));s.parts.length>n.parts.length&&(s.parts.length=n.parts.length)}else{var i=[];for(o=0;o<n.parts.length;o++)i.push(b(n.parts[o]));c[n.id]={id:n.id,refs:1,parts:i}}}}function C(){var t=document.createElement("style");return t.type="text/css",i.appendChild(t),t}function b(t){var e,n,s=document.querySelector("style["+m+'~="'+t.id+'"]');if(s){if(d)return u;s.parentNode.removeChild(s)}if(h){var o=r++;s=a||(a=C()),e=g.bind(null,s,o,!1),n=g.bind(null,s,o,!0)}else s=C(),e=E.bind(null,s),n=function(){s.parentNode.removeChild(s)};return e(t),function(s){if(s){if(s.css===t.css&&s.media===t.media&&s.sourceMap===t.sourceMap)return;e(t=s)}else n()}}var v,y=(v=[],function(t,e){return v[t]=e,v.filter(Boolean).join("\n")});function g(t,e,n,s){var o=n?"":s.css;if(t.styleSheet)t.styleSheet.cssText=y(e,o);else{var c=document.createTextNode(o),i=t.childNodes;i[e]&&t.removeChild(i[e]),i.length?t.insertBefore(c,i[e]):t.appendChild(c)}}function E(t,e){var n=e.css,s=e.media,o=e.sourceMap;if(s&&t.setAttribute("media",s),l.ssrId&&t.setAttribute(m,e.id),o&&(n+="\n/*# sourceURL="+o.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */"),t.styleSheet)t.styleSheet.cssText=n;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(n))}}}},e={};function n(s){var o=e[s];if(void 0!==o)return o.exports;var c=e[s]={id:s,exports:{}};return t[s](c,c.exports,n),c.exports}n.n=t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return n.d(e,{a:e}),e},n.d=(t,e)=>{for(var s in e)n.o(e,s)&&!n.o(t,s)&&Object.defineProperty(t,s,{enumerable:!0,get:e[s]})},n.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e),(()=>{"use strict";n(444),void 0===Craft.SocialMediaConnect&&(Craft.SocialMediaConnect={}),Craft.SocialMediaConnect.ComposeShare=Garnish.Base.extend({init(t,e){this.id=t,this.settings=e,this.currentAccount=this.settings.accounts[0]||null,this.draft=!!this.settings.draft,this.params={entryId:this.settings.entryId,siteId:this.settings.siteId},this.currentAccount&&this.insertShareToSocialButton()},insertShareToSocialButton(){const t=document.createElement("button");t.type="button",t.className="btn",t.textContent=Craft.t("social-media-connect","Post to Social Media"),t.addEventListener("click",(()=>this.showComposer()));const e=document.getElementById("action-buttons");e.insertBefore(t,e.firstElementChild)},showComposer(){this.$body=document.createElement("div"),this.$body.className="smc-compose-share__body body",this.$submitButton=document.createElement("button"),this.$submitButton.type="submit",this.$submitButton.className="btn submit smc-compose-share__submit",this.$submitButton.textContent=Craft.t("social-media-connect",this.draft?"Save post to {account}":"Post to {account}",{account:this.currentAccount.name});const t=this.accountSwitcher(),e=document.createElement("button");e.type="button",e.className="btn",e.textContent=Craft.t("social-media-connect","Cancel");const n=document.createElement("div");n.className="buttons smc-compose-share__buttons",n.appendChild(e),n.appendChild(this.$submitButton);const s=document.createElement("footer");s.className="footer smc-compose-share__footer",s.appendChild(n);const o=document.createElement("form");o.className="smc-compose-share__form",o.appendChild(t),o.appendChild(this.$body),o.appendChild(s);const c=document.createElement("form");c.className="modal smc-compose-share",c.appendChild(o),this.switchAccounts(this.currentAccount);const i=new Garnish.Modal(c,{hideOnEsc:!0,hideOnShadeClick:!1,onHide(){i.destroy(),c.remove()}});o.addEventListener("submit",(t=>{t.preventDefault(),this.$submitButton.disabled=!0;const e=Garnish.getPostData(o),n=Craft.expandPostArray(e);Craft.postActionRequest("social-media-connect/compose/post-share",{...this.params,accountId:this.currentAccount.id,...n},(t=>{if(t.success)i.hide(),Craft.cp.displayNotice(Craft.t("social-media-connect",this.draft?"Post to {account} was saved and will be published with the entry":"Successful posted to {account}",{account:this.currentAccount.name}));else{if(this.$body.innerHTML=t.fields,t.error){const e=document.createElement("div");e.className="error",e.textContent=t.error,this.$body.insertBefore(e,this.$body.firstElementChild)}Craft.initUiElements(this.$body),this.$submitButton.disabled=!1}}))})),e.addEventListener("click",(t=>{t.preventDefault(),i.hide()}))},switchAccounts(t){this.currentAccount=t,this.$submitButton.disabled=!0,this.$submitButton.textContent=Craft.t("social-media-connect",this.draft?"Save post to {account}":"Post to {account}",{account:t.name}),Craft.postActionRequest("social-media-connect/compose/fields",{...this.params,accountId:t.id},(t=>{this.$body.innerHTML=t.fields,Craft.initUiElements(this.$body),this.$submitButton.disabled=!1}))},accountSwitcher(){const t=document.createElement("button"),e=document.createElement("span");e.className="smc-menu-icon",e.ariaHidden="true",e.innerHTML=this.currentAccount.icon,t.type="button",t.id=`${this.id}-account-switcher`,t.className="btn menubtn",t.textContent=this.currentAccount.name,t.title=Craft.t("social-media-connect","Post to {account}",{account:this.currentAccount.name}),t.insertBefore(e,t.firstChild);const n=document.createElement("ul");this.settings.accounts.forEach((t=>{const e=document.createElement("li"),s=document.createElement("a"),o=document.createElement("span");o.className="smc-menu-icon",o.ariaHidden="true",o.innerHTML=t.icon,s.dataset.accountId=t.id,s.textContent=t.name,s.title=Craft.t("social-media-connect","Use {account}",{account:t.name}),s.insertBefore(o,s.firstChild),e.appendChild(s),n.appendChild(e)}));const s=document.createElement("div");s.className="menu",s.appendChild(n);const o=document.createElement("div");o.className="smc-compose-share__account-switcher",o.appendChild(t),o.appendChild(s);const c=new Garnish.CustomSelect(s),i=new Garnish.MenuBtn(t,c);return c.on("optionselect",(e=>{const{selectedOption:n}=e,s=this.settings.accounts.find((t=>t.id==n.dataset.accountId));t.textContent=s.name,t.title=Craft.t("social-media-connect","Post to {account}",{account:s.name});const o=n.querySelector(".smc-menu-icon");o&&t.insertBefore(o.cloneNode(!0),t.firstChild),this.switchAccounts(s)})),this.on("destory",(()=>{i.destroy()})),o}})})()})();
//# sourceMappingURL=compose-share.js.map