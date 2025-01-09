import React from 'react';
 
import ReactDOM from 'react-dom';
 
import Skeleton from './components/Skeleton';

const reactAppContainer = document.getElementById('react-app');

if (reactAppContainer) {
  ReactDOM.render(<Skeleton />, reactAppContainer);
}