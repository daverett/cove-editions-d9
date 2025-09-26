import React from 'react';
import { createRoot } from 'react-dom/client';
import Index from './index'; // Your main React component
 
import Skeleton from './Skeleton';

const container = document.getElementById('react-app');
const root = createRoot(container); // Create a root


if (container) {
  root.render(<Skeleton />);  // Render your application
}