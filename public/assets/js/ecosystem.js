/* Ecosistema Carrousel · comportamiento visual compartido
 * @version 1.3.0 (misma versión que ecosystem.css; historial en docs/ECOSISTEMA.md)
 * Archivo IDÉNTICO en todas las apps. Solo presentación: no envía datos ni cambia lógica.
 */
(()=>{'use strict';
const desktop=window.matchMedia('(min-width:761px)');

// Tablas más anchas que su contenedor: activa la columna de acciones fija (ecosystem.css §3).
function markOverflowingTables(){
  document.querySelectorAll('.data-table-wrap').forEach(w=>w.classList.toggle('is-overflowing',w.scrollWidth-w.clientWidth>8));
}

// "Más acciones" dentro de una tabla desplazable: el panel se abre fijo sobre la pantalla
// para que no lo recorte el contenedor.
function placePanel(details){
  const panel=details.querySelector('.row-action-disclosure-panel');
  const summary=details.querySelector('summary');
  if(!panel||!summary)return;
  if(!details.open||!desktop.matches){panel.classList.remove('is-floating');panel.style.top=panel.style.left='';return;}
  panel.classList.add('is-floating');
  const r=summary.getBoundingClientRect();
  const w=panel.offsetWidth,h=panel.offsetHeight;
  const below=r.bottom+6+h<=window.innerHeight;
  panel.style.top=Math.max(8,below?r.bottom+6:r.top-6-h)+'px';
  panel.style.left=Math.max(8,Math.min(window.innerWidth-w-8,r.right-w))+'px';
}
function closeFloatingPanels(){
  document.querySelectorAll('.data-table .row-action-disclosure[open]').forEach(d=>{d.open=false;placePanel(d);});
}

document.addEventListener('toggle',e=>{
  const d=e.target;
  if(d instanceof HTMLDetailsElement&&d.matches('.data-table .row-action-disclosure'))placePanel(d);
},true);
document.querySelectorAll('.data-table-wrap').forEach(w=>w.addEventListener('scroll',closeFloatingPanels,{passive:true}));
window.addEventListener('scroll',closeFloatingPanels,{passive:true});
window.addEventListener('resize',()=>{markOverflowingTables();closeFloatingPanels();});
markOverflowingTables();
// Algunas apps normalizan tablas después de cargar; se recalcula cuando todo terminó.
window.addEventListener('load',markOverflowingTables);

// Botón de tema del ecosistema (login y demás pantallas que lo usen): alterna claro/oscuro.
// Misma clave en todas las apps, así el modo elegido se respeta al pasar de una a otra.
document.addEventListener('click',e=>{
  const btn=e.target instanceof Element?e.target.closest('[data-eco-theme-toggle]'):null;
  if(!btn)return;
  const next=document.documentElement.dataset.theme==='dark'?'light':'dark';
  document.documentElement.dataset.theme=next;
  document.documentElement.style.colorScheme=next;
  try{localStorage.setItem('carrousel-theme',next);}catch(err){}
});

// "Procesando" y avisos: misma animación en todas las apps (ecosystem.css §10).
// EcoUI.busy('Guardando…'), EcoUI.idle() y EcoUI.toast('Listo', 'success'|'warning'|'danger'|'info', ms).
// Si la pantalla no trae el marcado, se crea al primer uso.
function ensureStatus(){
  let el=document.getElementById('global-action-status');
  if(!el){
    el=document.createElement('div');
    el.id='global-action-status';el.className='global-action-status';
    el.setAttribute('role','status');el.setAttribute('aria-live','polite');el.setAttribute('aria-hidden','true');
    el.innerHTML='<div class="global-action-card"><span class="global-action-spinner" aria-hidden="true"></span><strong>Procesando información</strong><span data-action-message>Espera un momento…</span></div>';
    document.body.appendChild(el);
  }
  return el;
}
function ensureToasts(){
  let el=document.getElementById('app-toast-container');
  if(!el){
    el=document.createElement('div');
    el.id='app-toast-container';el.className='app-toast-container';
    el.setAttribute('aria-live','polite');el.setAttribute('aria-atomic','false');
    document.body.appendChild(el);
  }
  return el;
}
function busy(message){
  const el=ensureStatus();
  const msg=el.querySelector('[data-action-message]');
  if(msg)msg.textContent=message||'Espera un momento…';
  el.classList.add('show');el.setAttribute('aria-hidden','false');
}
function idle(){
  const el=document.getElementById('global-action-status');
  if(!el)return;
  el.classList.remove('show');el.setAttribute('aria-hidden','true');
}
function toast(message,type,duration){
  if(!message)return;
  const kind=['success','warning','danger','info'].includes(type)?type:(type==='error'?'danger':'info');
  const item=document.createElement('div');
  item.className='app-toast '+kind;
  item.setAttribute('role',kind==='danger'?'alert':'status');
  const text=document.createElement('span');
  text.className='app-toast-message';text.textContent=String(message);
  const close=document.createElement('button');
  close.className='app-toast-close';close.type='button';close.setAttribute('aria-label','Cerrar');close.textContent='×';
  item.append(text,close);ensureToasts().appendChild(item);
  const remove=()=>item.remove();
  close.addEventListener('click',remove);
  const ms=duration===undefined?3600:duration;
  if(ms>0)window.setTimeout(remove,ms);
}
window.EcoUI=Object.freeze({busy,idle,toast});
// Al volver con "Atrás" el navegador restaura la página: nunca debe quedar el "Procesando" abierto.
window.addEventListener('pageshow',idle);
})();
