import React, {useState} from 'react';
import axios from 'axios';

export default function Login({onLogin}){
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [err, setErr] = useState(null);

  const submit = async ()=>{
    try{
      const res = await axios.post('http://localhost:8000/api/login',{email,password});
      const token = res.data.token;
      if (token){
        onLogin(token);
      }
    }catch(e){
      setErr('Login falhou');
    }
  };

  return (
    <div style={{padding:20}}>
      <h3>Login</h3>
      {err && <div style={{color:'red'}}>{err}</div>}
      <div>
        <input placeholder="email" value={email} onChange={e=>setEmail(e.target.value)} />
      </div>
      <div>
        <input placeholder="senha" type="password" value={password} onChange={e=>setPassword(e.target.value)} />
      </div>
      <div>
        <button onClick={submit}>Entrar</button>
      </div>
    </div>
  );
}
