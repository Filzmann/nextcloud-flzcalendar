import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const source=readFileSync(new URL('../../js/admin-access.js',import.meta.url),'utf8');
const element=()=>({checked:false,disabled:false,textContent:'',listeners:{},dataset:{},children:[],elements:{enabled:{checked:true}},addEventListener(type,listener){this.listeners[type]=listener;},append(child){this.children.push(child);},replaceChildren(){this.children=[];},setAttribute(name,value){this[name]=value;}});
const form=element();const history=element();const status=element();const requests=[];
const context={
    FormData:class{get(name){return {enabled:'on',targetUid:' admin-target ',durationMinutes:'60'}[name]??null;}},
    document:{getElementById(id){return {'adc-full-access-form':form,'adc-full-access-history':history,'adc-full-access-status':status}[id]||null;},createElement(){return element();}},
    window:{LocalBase:{api:{ApiClient:class{async request(path,options={}){requests.push({path,options});return path==='/api/admin/full-access'&&!options.method?{history:[]}:{};}}}}},Intl,Date,encodeURIComponent,JSON,Promise,console,
};
runInNewContext(source,context,{filename:fileURLToPath(new URL('../../js/admin-access.js',import.meta.url))});
for(let index=0;index<8;index++)await Promise.resolve();
if(JSON.stringify(requests.shift())!==JSON.stringify({path:'/api/admin/full-access',options:{}}))throw new Error('DPO-Historie wird nicht geladen.');
await form.listeners.submit({preventDefault(){}});
if(JSON.stringify(requests.splice(0,2))!==JSON.stringify([{path:'/api/admin/full-access',options:{method:'POST',body:JSON.stringify({targetUid:'admin-target',durationMinutes:60})}},{path:'/api/admin/full-access',options:{}}]))throw new Error('Freigabe verwendet nicht den geschützten API-Vertrag.');
const revoke=element();revoke.dataset.revokeUid='admin-target';
await history.listeners.click({target:{closest(){return revoke;}}});
if(JSON.stringify(requests)!==JSON.stringify([{path:'/api/admin/full-access/admin-target',options:{method:'DELETE'}},{path:'/api/admin/full-access',options:{}}]))throw new Error('Widerruf verwendet nicht den UID-genauen API-Vertrag.');
if(status.textContent!=='Der Vollzugriff wurde widerrufen.')throw new Error('Widerruf wird nicht verständlich bestätigt.');
console.log('Admin access smoke: OK');
