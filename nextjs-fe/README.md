# Next.js Frontend - My Life Management

This is a [Next.js](https://nextjs.org) project configured as a **Single Page Application (SPA)** with **full Client-Side Rendering (CSR)**.

## 🎯 Architecture

This application is built using:

- **Single Page Application (SPA)** - All pages are rendered on the client side
- **Full Client-Side Rendering (CSR)** - No server-side rendering (SSR) or static site generation (SSG)
- **Static Export** - Configured with `output: 'export'` in `next.config.ts`
- **No Node.js Server Required** - Can be deployed to any static hosting (CDN, nginx, etc.)

### Why SPA/CSR?

- ✅ Full control over rendering on the client
- ✅ Can be deployed as static files
- ✅ No server runtime required
- ✅ Works perfectly with nginx proxy
- ✅ Ideal for applications with authentication and dynamic content

### Single Source of Truth for Docker
Note that all Docker-related configuration (`Dockerfile`, `docker-compose.yml`) is removed from this directory to enforce a single source of truth. The `.dockerignore` remains here as required by the Docker build context. All Docker commands must be run from the root `docker/` directory.

## 🚀 Getting Started

### Development (Docker)

The recommended way to run this application is via Docker:

```bash
cd ../docker
docker compose up ml-nextjs
```

The application will be available at:
- Via nginx: `http://localhost:81`
- Direct access: `http://localhost:3456`

### Development (Local)

If you want to run locally without Docker:

```bash
npm install
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) with your browser to see the result.

**Note**: The dev script uses `--webpack` flag for better compatibility with static export mode.

## 📁 Project Structure

```
src/
├── app/              # App router pages
├── components/       # Reusable UI components
├── hooks/           # Custom React hooks
├── redux/           # Redux store and slices
├── lib/             # Utility functions
└── styles/          # Global styles
```

## 🔧 Configuration

### Next.js Config

The application is configured in `next.config.ts`:

```typescript
{
  output: 'export',        // Static export mode
  images: {
    unoptimized: true,     // Disable image optimization
  }
}
```

### Environment Variables

Create a `.env.local` file for local development:

```env
NEXT_PUBLIC_API_URL=http://localhost:81/api
```

## 🏗️ Building for Production

To build the static files:

```bash
npm run build
```

This will generate static HTML/CSS/JS files in the `out/` directory, which can be deployed to:
- Any static hosting service (Vercel, Netlify, etc.)
- CDN (CloudFront, Cloudflare, etc.)
- Web servers (nginx, Apache, etc.)

## 🐳 Docker Development

The Docker setup includes:

- **Pre-installed dependencies** in the image for fast startup
- **Hot-reload support** with file watching polling
- **Smart dependency caching** - only updates when package.json changes
- **Optimized volume mounts** for better performance

Performance metrics:
- Container startup: < 3 seconds
- Container restart: ~1.5 seconds
- Server ready: ~2 seconds

## 📚 Learn More

To learn more about Next.js and SPA development:

- [Next.js Documentation](https://nextjs.org/docs) - learn about Next.js features and API
- [Next.js Static Exports](https://nextjs.org/docs/app/building-your-application/deploying/static-exports) - learn about static export mode
- [Learn Next.js](https://nextjs.org/learn) - an interactive Next.js tutorial

## 🔐 Authentication

Default credentials for development:
- Username: `root`
- Password: `12345678`

## 📝 Notes

- This application uses **webpack mode** instead of Turbopack for better compatibility with static export
- File watching uses **polling** in Docker environments for reliable hot-reload
- All rendering happens on the client - no server-side code execution
