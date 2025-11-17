import React, {useState, useEffect} from 'react';
import ChatWindow from './components/ChatWindow';
import Login from './components/Login';
import Register from './components/Register';
import ConversationsList from './components/ConversationsList';

export default function App(){
  const [user, setUser] = useState(null);
  const [conversationId, setConversationId] = useState(1);
  const [token, setToken] = useState(() => localStorage.getItem('token'));

  useEffect(()=>{
    if (token){
      // In a real app we'd fetch user profile. For demo, keep simple
      setUser({id:1,name:'Demo'});
      localStorage.setItem('token', token);
    } else {
      setUser(null);
      localStorage.removeItem('token');
    }
  },[token]);

  if (!token) return (
    <div style={{display:'flex',gap:20}}>
      <Login onLogin={(t)=>setToken(t)} />
      <Register onRegistered={()=>{alert('Registrado, faça login')}} />
    </div>
  );

  return (
    <div style={{display:'flex',height:'100vh'}}>
      <div style={{width:300,borderRight:'1px solid #ddd',padding:8}}>
        <ConversationsList token={token} onSelect={(id)=>setConversationId(id)} />
      </div>
      <div style={{flex:1}}>
        <ChatWindow token={token} user={user} conversationId={conversationId} />
      </div>
    </div>
  );
}
