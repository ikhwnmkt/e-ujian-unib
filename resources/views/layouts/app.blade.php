<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'E-Ujian Universitas Bengkulu')</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:'Lato','Segoe UI',Arial,sans-serif;background:#f4f5f7;color:#222}
a{text-decoration:none;color:inherit}
.topbar{background:#fff;display:flex;justify-content:space-between;align-items:center;padding:12px 32px;border-bottom:2px solid #ddd}
.brand{display:flex;align-items:center;gap:10px;font-weight:700;color:#1d2b6b}
.brand img{height:48px}
.user{display:flex;align-items:center;gap:12px;text-align:right;font-size:13px}
.user b{display:block}.user small{color:#777;font-weight:700}
.avatar{width:42px;height:42px;border-radius:50%;border:1px solid #3182e0;background:#fff center/cover}
.container{max-width:1100px;margin:24px auto;padding:0 20px}
h2{margin:0 0 6px;font-size:20px}h3{font-size:15px;margin:28px 0 12px}
hr{border:0;border-top:1px solid #ddd;margin:8px 0 16px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
.card{background:#fff;border-radius:6px;box-shadow:0 1px 6px rgba(0,0,0,.18);overflow:hidden}
.card .body{padding:14px 16px;font-size:14px;line-height:1.7}
.card .body b{font-size:15px;display:block;margin-bottom:6px}
.card .foot{display:flex;border-top:1px solid #e5e5e5;padding:8px 14px;gap:8px;justify-content:space-between;align-items:center}
.card .foot.split>*{flex:1;text-align:center}
.btn{display:inline-block;border:0;border-radius:4px;padding:6px 14px;color:#fff;font-size:13px;cursor:pointer;font-family:inherit}
.btn-blue{background:#3182e0}.btn-red{background:#e0393e}.btn-green{background:#5cd24a}.btn-gray{background:#999}
.btn,.btn-pill,.plus,.tabs a,button{transition:background-color .2s ease,color .2s ease,border-color .2s ease,filter .2s ease,transform .2s ease}
.btn:hover,.btn-pill:hover,.plus:hover,.tabs a:hover,button:hover{filter:brightness(.9);transform:translateY(-1px)}
.btn:focus-visible,.btn-pill:focus-visible,.plus:focus-visible,.tabs a:focus-visible,button:focus-visible{outline:3px solid rgba(49,130,224,.35);outline-offset:2px}
.btn.off{opacity:.45;pointer-events:none}
.plus{width:30px;height:30px;border-radius:50%;background:#3182e0;color:#fff;font-size:22px;display:inline-flex;align-items:center;justify-content:center}
.row{display:flex;justify-content:space-between;align-items:center}
.tabs{display:flex;gap:10px;margin-bottom:20px}
.tabs a{padding:7px 22px;border-radius:20px;font-size:14px}
.tabs a.active{background:#3182e0;color:#fff}
.panel{background:#fff;border-radius:10px;padding:28px;box-shadow:0 1px 6px rgba(0,0,0,.15)}
label{display:block;font-size:14px;margin:12px 0 4px}
input,select,textarea{width:100%;padding:9px 14px;border:1px solid #3182e0;border-radius:20px;font-family:inherit;font-size:14px}
textarea{border-radius:12px}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-box{background:#fff;border:1px solid #888;border-radius:28px;width:380px;padding:30px 22px}
.login-box .logo{text-align:center;margin-bottom:12px}.login-box .logo img{height:80px}
.or{display:flex;align-items:center;gap:8px;color:#999;font-size:13px;margin:16px 0}
.or:before,.or:after{content:'';flex:1;border-top:1px solid #ccc}
.btn-pill{display:block;width:100%;text-align:center;padding:9px;border-radius:20px;border:1px solid #888;background:#fff;font-size:14px;margin-top:8px;cursor:pointer}
.btn-pill.primary{background:#3182e0;color:#fff;border-color:#3182e0}
.alert{padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:14px}
.ok{background:#e3f6df;color:#256c1a}.err{background:#fde4e4;color:#a12525}
table{width:100%;border-collapse:collapse;font-size:14px;background:#fff}
th,td{padding:10px 12px;border-bottom:1px solid #eee;text-align:left}th{background:#f0f4fb}
.profile-head{text-align:center}.profile-head .avatar{width:100px;height:100px;margin:0 auto 8px}
.pgrid{display:grid;grid-template-columns:1fr 1fr;gap:0 30px;font-size:14px}
.pgrid div{margin-bottom:14px}.pgrid b{display:block}
.opt{display:block;padding:8px 12px;border:1px solid #ddd;border-radius:8px;margin:6px 0;cursor:pointer}
.opt input{width:auto;margin-right:8px}
.timer{position:sticky;top:0;background:#1d2b6b;color:#fff;padding:10px 20px;text-align:center;font-weight:700;z-index:5}
.good{background:#e3f6df}.bad{background:#fde4e4}
@media(max-width:700px){.pgrid{grid-template-columns:1fr}.topbar{padding:10px 14px}}
</style>
</head>
<body>
@auth
<header class="topbar">
  <a href="{{ route('dashboard') }}" class="brand">
    <img src="{{ asset('images/logo-unib.png') }}" alt="Logo Universitas Bengkulu">
    <span>UNIVERSITAS BENGKULU</span>
  </a>
  <div class="user">
    <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->subtitle }}</small></div>
    <a href="{{ route('profil') }}" class="avatar" title="Profil"
       style="{{ auth()->user()->avatar ? 'background-image:url('.asset('storage/'.auth()->user()->avatar).')' : '' }}"></a>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-gray">Keluar</button></form>
  </div>
</header>
@endauth
@yield('content')
</body>
</html>
