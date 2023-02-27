import rateLimit from 'express-rate-limit';

const rateLimitMiddleware = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 min in milliseconds for test
  max: 100,
  message: { message: "Request error, you have reached maximum retries. Please try again after 30 minutes" },
  statusCode: 429,
  headers: true,
});

export default rateLimitMiddleware 