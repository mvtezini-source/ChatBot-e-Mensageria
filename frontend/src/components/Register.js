import React, {useState} from 'react';
import axios from 'axios';

export default function Register({onRegistered}){
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [err, setErr] = useState(null);

  const submit = async ()=>{
    try{
      await axios.post('http://localhost:8000/api/register',{name,email,password});
      onRegistered && onRegistered();
    }catch(e){
      setErr('Falha ao registrar');
    }
  };

  return (
    <div style={{padding:20}}>
      <h3>Registrar</h3>
      {err && <div style={{color:'red'}}>{err}</div>}
      <div>
        <input placeholder="Nome" value={name} onChange={e=>setName(e.target.value)} />
      </div>
      <div>
        <input placeholder="email" value={email} onChange={e=>setEmail(e.target.value)} />
      </div>
      <div>
        <input placeholder="senha" type="password" value={password} onChange={e=>setPassword(e.target.value)} />
      </div>
      <div>
        <button onClick={submit}>Registrar</button>
      </div>
    </div>
  );
}
