import React, {useEffect, useState} from 'react';
import axios from 'axios';

export default function ConversationsList({token, onSelect}){
  const [convs, setConvs] = useState([]);

  useEffect(()=>{
    axios.get('http://localhost:8000/api/conversations',{headers:{Authorization:'Bearer '+token}})
      .then(r=>setConvs(r.data))
      .catch(()=>{});
  },[token]);

  return (
    <div>
      <h3>Conversas</h3>
      <ul>
        {convs.map(c=>(<li key={c.id} onClick={()=>onSelect(c.id)}>{c.title || ('Conversa '+c.id)}</li>))}
      </ul>
    </div>
  );
}
