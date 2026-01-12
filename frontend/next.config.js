const BACKEND = process.env.BACKEND_INTERNAL_URL || "http://127.0.0.1:8081";

module.exports = {
  async rewrites() {
    return [
      { source: "/api/:path*", destination: `${BACKEND}/api/:path*` },
      { source: "/uploads/:path*", destination: `${BACKEND}/uploads/:path*` }
    ];
  }
};
