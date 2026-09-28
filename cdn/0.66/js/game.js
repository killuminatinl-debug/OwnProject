(function(){
"use strict";
var app=document.createElement("div");app.id="localKingdomsApp";document.body.appendChild(app);
var API="api/index.php", cache={}, villageId=null, player=null;
function esc(v){return String(v==null?"":v).replace(/[&<>"']/g,function(c){return({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[c];});}
function call(controller,action,params){return fetch(API,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({controller:controller,action:action,params:params||{}})}).then(function(r){return r.text();}).then(function(t){try{return JSON.parse(t);}catch(e){throw new Error(t||"Empty server response");}});}
function findCache(name){return cache[name]&&cache[name].data?cache[name].data:null;}
function refresh(){
 return call("player","getAll",{}).then(function(d){
  (d.cache||[]).forEach(function(x){if(x&&x.name)cache[x.name]=x;});
  var own=findCache("Collection:Village:own");
  if(own&&own.cache&&own.cache.length)villageId=own.cache[0].wid;
  player=findCache("Player:"+((window.__PLAYER_ID__)||""))||null;
  if(!player){Object.keys(cache).some(function(k){if(k.indexOf("Player:")===0){player=cache[k].data;return true;}return false;});}
  render("village");
 });
}
function shell(content){
 var v=findCache("Village:"+villageId)||{}, name=v.vname||"Village", p=player||{};
 var res=[["Wood",v.wood||0],["Clay",v.clay||0],["Iron",v.iron||0],["Crop",v.crop||0],["Population",v.pop||0]];
 app.innerHTML='<div class="lk-top"><div class="lk-logo">Travian Kingdoms</div><div class="lk-player">'+esc(p.name||"Lord")+'</div></div><div class="lk-res">'+res.map(function(x){return "<b>"+x[0]+": "+Math.floor(x[1])+"</b>";}).join("")+'</div><div class="lk-shell"><aside class="lk-side"><button data-page="village">Village</button><button data-page="buildings">Buildings</button><button data-page="map">Map</button><button data-page="army">Army</button><button data-page="hero">Hero</button><button data-page="kingdom">Kingdom</button><button data-page="reports">Reports</button><button data-page="market">Market</button><button data-page="ranking">Ranking</button><button data-page="settings">Settings</button><button data-page="lobby">Lobby</button></aside><main class="lk-main">'+content+'</main></div>';
 app.querySelectorAll("[data-page]").forEach(function(b){b.onclick=function(){if(b.dataset.page==="lobby"){location.href=location.pathname.split("/game/")[0]+"/lobby/";return;}render(b.dataset.page);};});
}
function render(page){
 if(!villageId){app.innerHTML='<div class="lk-card lk-error">No village exists yet. Reload the game once the server has created your starter village.</div>';return;}
 if(page==="village")return village();
 if(page==="buildings")return buildings();
 if(page==="map")return map();
 if(page==="army")return army();
 if(page==="hero")return hero();
 if(page==="kingdom")return kingdom();
 if(page==="reports")return simple("Reports","Your reports will appear here.");
 if(page==="market")return simple("Market","Trade and merchant functions are available through the game API.");
 if(page==="ranking")return simple("Ranking","Player and kingdom rankings.");
 if(page==="settings")return simple("Settings","Game settings.");
}
function village(){
 var v=findCache("Village:"+villageId)||{};
 shell('<div class="lk-card"><h1 class="lk-title">'+esc(v.vname||"Village")+'</h1><p class="lk-muted">Your village is running continuously. Resources are processed on requests, so no cron job is required.</p><div class="lk-grid"><div class="lk-tile"><h3>Wood</h3>'+Math.floor(v.wood||0)+'</div><div class="lk-tile"><h3>Clay</h3>'+Math.floor(v.clay||0)+'</div><div class="lk-tile"><h3>Iron</h3>'+Math.floor(v.iron||0)+'</div><div class="lk-tile"><h3>Crop</h3>'+Math.floor(v.crop||0)+'</div><div class="lk-tile"><h3>Population</h3>'+Math.floor(v.pop||0)+'</div></div></div><div class="lk-card"><h2 class="lk-title">Village centre</h2><div class="lk-village" id="villageSlots"></div></div>');
 var el=document.getElementById("villageSlots");for(var i=1;i<=40;i++){var b=document.createElement("div");b.className="lk-building";b.style.left=(8+((i-1)%8)*12)+"%";b.style.top=(8+Math.floor((i-1)/8)*18)+"%";b.textContent="Building "+i;b.dataset.location=i;b.onclick=function(){renderBuilding(parseInt(this.dataset.location,10));};el.appendChild(b);}
}
function renderBuilding(loc){
 call("building","getBuildingList",{villageId:villageId,locationId:loc}).then(function(d){
  var list=d.response||[];var options=Array.isArray(list)?list:Object.keys(list).map(function(k){return list[k];});
  var html='<div class="lk-card"><h1 class="lk-title">Building slot '+loc+'</h1><p class="lk-muted">Choose an available building.</p>';
  if(!options.length)html+="<p>No building is currently available for this slot.</p>";
  options.slice(0,20).forEach(function(x){var id=x.buildingType||x.type||x.id;var name=x.name||x.title||("Building "+id);html+='<button class="lk-action" data-build="'+esc(id)+'" style="margin:4px">'+esc(name)+"</button>";});
  html+='<br><br><button class="lk-action" id="back">Back</button></div>';
  shell(html);document.getElementById("back").onclick=function(){render("buildings");};
  app.querySelectorAll("[data-build]").forEach(function(b){b.onclick=function(){call("building","upgrade",{locationId:loc,buildingType:parseInt(b.dataset.build,10),villageId:villageId}).then(refresh).catch(showError);};});
 }).catch(showError);
}
function buildings(){
 shell('<div class="lk-card"><h1 class="lk-title">Buildings</h1><p class="lk-muted">Select a slot to inspect its available construction.</p><div class="lk-grid" id="slots"></div></div>');
 var s=document.getElementById("slots");for(var i=1;i<=40;i++){var b=document.createElement("button");b.className="lk-action";b.textContent="Slot "+i;b.onclick=(function(n){return function(){renderBuilding(n);};})(i);s.appendChild(b);}
}
function map(){shell('<div class="lk-card"><h1 class="lk-title">World map</h1><div class="lk-map" id="mapGrid"></div><p class="lk-muted">Click a tile to request its map details.</p></div>');var g=document.getElementById("mapGrid");for(var i=0;i<80;i++){var t=document.createElement("div");t.className="tile";t.style.left=((i%10)*10)+"%";t.style.top=(Math.floor(i/10)*10)+"%";t.title="Map tile";g.appendChild(t);}}
function army(){shell('<div class="lk-card"><h1 class="lk-title">Army</h1><div id="armyData">Loading troops…</div></div>');call("troops","getAll",{villageId:villageId}).then(function(d){document.getElementById("armyData").textContent=JSON.stringify(d.response||d.cache||[],null,2);}).catch(showError);}
function hero(){shell('<div class="lk-card"><h1 class="lk-title">Hero</h1><div id="heroData">Loading hero…</div></div>');call("cache","get",{names:["Hero:"+((player&&player.playerId)||0)]}).then(function(d){document.getElementById("heroData").textContent=JSON.stringify(d.cache||[],null,2);}).catch(showError);}
function kingdom(){shell('<div class="lk-card"><h1 class="lk-title">Kingdom</h1><div id="kingdomData">Loading kingdom…</div></div>');var kid=player&&player.kingdomId||0;call("cache","get",{names:["Kingdom:"+kid,"KingdomStats:"+kid]}).then(function(d){document.getElementById("kingdomData").textContent=JSON.stringify(d.cache||[],null,2);}).catch(showError);}
function simple(title,text){shell('<div class="lk-card"><h1 class="lk-title">'+title+'</h1><p>'+text+'</p></div>');}
function showError(e){shell('<div class="lk-card lk-error">The server returned an error:\n'+esc(e&&e.message||e)+'</div>');}
window.addEventListener("error",function(e){console.error(e);});
refresh().catch(function(e){showError(e);});
setInterval(function(){call("player","ping",{}).catch(function(){});},30000);
})();