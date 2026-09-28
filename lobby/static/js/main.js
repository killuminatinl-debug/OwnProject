(function () {
"use strict";
function esc(v){return String(v==null?"":v).replace(/[&<>"']/g,function(c){return({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"})[c];});}
var root=document.getElementById("root");
if(!root)return;
var state=window.__INITIAL_STATE__||{}, logged=Number(state.cache&&state.cache["Player:"+(state.cache&&Object.keys(state.cache).length?Object.keys(state.cache)[0]:"0")] ? 1:0);
var playerId=0;
Object.keys(state.cache||{}).some(function(k){if(k.indexOf("Player:")===0){playerId=parseInt(k.split(":")[1],10)||0;return true;}return false;});
var base=(location.pathname.indexOf("/lobby/")>=0?location.pathname.split("/lobby/")[0]:"");
var lobby=base+"/lobby/";
var mellon=base+"/mellon/";
var css=document.createElement("style");
css.textContent=".tk-home{min-height:100vh;background:linear-gradient(#18251d,#304936 45%,#151b16);font-family:Arial,sans-serif;color:#eee}.tk-top{height:62px;background:#101712;box-shadow:0 2px 8px #000;display:flex;align-items:center;padding:0 28px}.tk-logo{font-family:Georgia,serif;font-size:28px;color:#e8d49b;text-shadow:0 2px 2px #000}.tk-wrap{max-width:1100px;margin:35px auto;padding:0 20px}.tk-panel{background:rgba(20,27,21,.94);border:1px solid #6e5b32;border-radius:6px;box-shadow:0 5px 25px #000;padding:28px}.tk-title{font:32px Georgia,serif;color:#e7d39a;margin:0 0 8px}.tk-sub{color:#bbb;margin-bottom:25px}.tk-world{display:flex;justify-content:space-between;align-items:center;background:linear-gradient(#354d39,#202e23);border:1px solid #7a693f;border-radius:5px;padding:18px;margin:12px 0}.tk-btn{display:inline-block;border:1px solid #9c8148;border-radius:4px;background:linear-gradient(#a88b4b,#655128);color:#fff;text-decoration:none;padding:10px 18px;cursor:pointer;box-shadow:inset 0 1px rgba(255,255,255,.25)}.tk-btn:hover{filter:brightness(1.15)}.tk-muted{color:#aaa;font-size:13px}.tk-login{max-width:420px;margin:70px auto}.tk-login input{display:block;width:100%;box-sizing:border-box;margin:10px 0;padding:12px;border:1px solid #635738;background:#101711;color:#fff;border-radius:4px}.tk-nav{margin-left:auto}.tk-nav a{margin-left:10px}.tk-error{color:#e7a5a5;padding:10px}.tk-ok{color:#b9d99b;padding:10px}";
document.head.appendChild(css);
function api(payload){
return fetch(lobby+"api/index.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(payload)}).then(function(r){return r.json();});
}
function render(){
var loggedIn=playerId>0;
root.innerHTML='<div class="tk-home"><div class="tk-top"><div class="tk-logo">Travian Kingdoms</div><div class="tk-nav">'+(loggedIn?'<a class="tk-btn" href="'+base+'/mellon/index.php?account/logout">Log out</a>':'<a class="tk-btn" href="'+mellon+'authentication/login">Log in</a>')+'</div></div><div class="tk-wrap"><div class="tk-panel"><h1 class="tk-title">'+(loggedIn?'Welcome back, '+esc((state.cache["Player:"+playerId]||{}).data?.avatarName||"Lord"):'Enter the Kingdoms')+'</h1><div class="tk-sub">Build your kingdom, develop villages and command your troops.</div><div id="worlds"><div class="tk-muted">Loading game worlds…</div></div></div></div></div>';
var play=lobby+"play.php?sid="+encodeURIComponent(w.consumersId);return '<div class="tk-world"><div><strong>'+esc(w.worldName)+'</strong><div class="tk-muted">Speed '+esc(w.speedGame)+'x · '+esc(w.speedTroops)+'x · '+esc(w.playersRegistered)+' players</div></div><a class="tk-btn" href="'+play+'">Play</a></div>';
}
function md5Fallback(){return "";}
// Always provide an actual login/register entry point; the original client bundle is absent.
if(!playerId){
root.innerHTML='<div class="tk-home"><div class="tk-top"><div class="tk-logo">Travian Kingdoms</div></div><div class="tk-wrap"><div class="tk-panel tk-login"><h1 class="tk-title">Travian Kingdoms</h1><div class="tk-sub">Log in or create your account.</div><a class="tk-btn" style="display:block;text-align:center" href="'+mellon+'authentication/login">Log in</a><br><a class="tk-btn" style="display:block;text-align:center" href="'+mellon+'registration/index">Create account</a></div></div></div>';
}else render();
})();