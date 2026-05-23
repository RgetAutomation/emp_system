<template>
  <div class="max-w-3xl mx-auto py-6 space-y-6">
    <div class="flex items-center justify-between mb-8">
      <h1 class="text-2xl font-black text-gray-900 flex items-center gap-2">
        <Globe class="w-6 h-6 text-blue-600" />
        Konnect <span class="text-sm font-bold text-gray-500 uppercase tracking-wider ml-2">Company Feed</span>
      </h1>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-2">
      <AlertCircle class="w-5 h-5" /> {{ error }}
    </div>

    <!-- Post Composer -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-4 flex gap-4">
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold shrink-0">
          {{ authStore.user?.name?.charAt(0) }}
        </div>
        <div class="flex-1">
          <textarea 
            v-model="newPost.content" 
            placeholder="What's on your mind? Share an update with the team..." 
            class="w-full bg-transparent border-0 focus:ring-0 resize-none text-gray-800 placeholder-gray-400 p-2 text-lg min-h-[80px]"
          ></textarea>
          
          <div v-if="newPost.imagePreview" class="relative mt-2 rounded-xl overflow-hidden bg-gray-100">
            <img :src="newPost.imagePreview" class="w-full max-h-64 object-contain" />
            <button @click="removeImage" class="absolute top-2 right-2 bg-gray-900/50 hover:bg-gray-900 text-white p-1.5 rounded-full backdrop-blur transition-colors">
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
      <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-100">
        <div class="flex items-center gap-2">
          <input type="file" ref="imageInput" @change="handleImageChange" accept="image/*" class="hidden" />
          <button @click="$refs.imageInput.click()" class="text-gray-500 hover:text-blue-600 hover:bg-blue-50 p-2 rounded-lg transition-colors flex items-center gap-2 font-bold text-sm">
            <Image class="w-5 h-5" />
            Add Image
          </button>
        </div>
        <button @click="submitPost" :disabled="!newPost.content.trim() && !newPost.image" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
          {{ posting ? 'Posting...' : 'Post to Feed' }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-10 h-10 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
    </div>

    <!-- Feed -->
    <div v-else class="space-y-6">
      <div v-for="post in posts" :key="post.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
        
        <!-- Post Header -->
        <div class="p-4 flex items-start justify-between">
          <div class="flex gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center text-blue-700 font-bold shrink-0">
              {{ post.user?.name?.charAt(0) }}
            </div>
            <div>
              <h3 class="font-bold text-gray-900 flex items-center gap-2">
                {{ post.user?.name }}
                <span v-if="post.user?.role === 'admin'" class="bg-indigo-100 text-indigo-700 text-[10px] uppercase px-1.5 py-0.5 rounded font-black">Admin</span>
              </h3>
              <p class="text-xs text-gray-500">{{ formatTime(post.created_at) }}</p>
            </div>
          </div>
          <!-- Delete Post (Admin or Author) -->
          <button v-if="isAdmin || post.user_id === authStore.user?.id" @click="deletePost(post.id)" class="text-gray-400 hover:text-red-600 p-1 rounded transition-colors" title="Delete Post">
            <Trash2 class="w-4 h-4" />
          </button>
        </div>

        <!-- Post Content -->
        <div class="px-4 pb-3">
          <p class="text-gray-800 whitespace-pre-wrap">{{ post.content }}</p>
        </div>

        <!-- Post Image -->
        <div v-if="post.image_path" class="w-full bg-gray-50 border-y border-gray-100 flex justify-center">
          <img :src="getImageUrl(post.image_path)" class="max-w-full max-h-[500px] object-contain" />
        </div>

        <!-- Post Actions -->
        <div class="px-4 py-3 flex items-center justify-between border-t border-gray-100">
          <div class="flex gap-1">
            <button @click="toggleLike(post)" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold text-sm transition-colors" :class="post.is_liked_by_me ? 'text-blue-600 bg-blue-50 hover:bg-blue-100' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'">
              <Heart :class="{'fill-blue-600': post.is_liked_by_me}" class="w-5 h-5 transition-transform active:scale-75" />
              <span>{{ post.likes_count }}</span>
            </button>
            <button @click="toggleComments(post.id)" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
              <MessageSquare class="w-5 h-5" />
              <span>{{ post.comments_count }}</span>
            </button>
          </div>
        </div>

        <!-- Comments Section -->
        <div v-if="activeCommentSection === post.id" class="bg-gray-50 border-t border-gray-100">
          <div class="p-4 space-y-4">
            <!-- Comment List -->
            <div v-for="comment in post.comments" :key="comment.id" class="flex gap-3">
              <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-xs shrink-0 mt-1">
                {{ comment.user?.name?.charAt(0) }}
              </div>
              <div class="flex-1 bg-white rounded-2xl rounded-tl-none p-3 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-sm text-gray-900">{{ comment.user?.name }}</span>
                  <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-400 font-medium">{{ formatTime(comment.created_at) }}</span>
                    <button v-if="isAdmin || comment.user_id === authStore.user?.id" @click="deleteComment(post.id, comment.id)" class="text-gray-300 hover:text-red-500 transition-colors">
                      <X class="w-3 h-3" />
                    </button>
                  </div>
                </div>
                <p class="text-sm text-gray-700">{{ comment.content }}</p>
              </div>
            </div>
            
            <div v-if="!post.comments.length" class="text-center text-sm text-gray-500 py-2">
              No comments yet. Be the first to reply!
            </div>

            <!-- Add Comment -->
            <div class="flex gap-3 mt-4 pt-4 border-t border-gray-200/60">
               <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs shrink-0">
                {{ authStore.user?.name?.charAt(0) }}
              </div>
              <div class="flex-1 flex gap-2">
                <input 
                  v-model="newComment[post.id]" 
                  @keyup.enter="submitComment(post.id)"
                  type="text" 
                  placeholder="Write a comment..." 
                  class="flex-1 px-4 py-2 text-sm bg-white border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <button @click="submitComment(post.id)" :disabled="!newComment[post.id]?.trim()" class="bg-blue-600 hover:bg-blue-700 text-white w-9 h-9 rounded-full flex items-center justify-center transition-colors disabled:opacity-50">
                  <Send class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="posts.length === 0" class="text-center py-20 bg-white rounded-2xl shadow-sm border border-gray-100">
        <Globe class="w-16 h-16 text-gray-200 mx-auto mb-4" />
        <h3 class="text-lg font-bold text-gray-500">Welcome to Konnect!</h3>
        <p class="text-gray-400">Be the first to share an update with the company.</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { Globe, Image, Heart, MessageSquare, Send, X, Trash2, AlertCircle } from 'lucide-vue-next';

const authStore = useAuthStore();
const isAdmin = computed(() => authStore.user?.role === 'admin');

const posts = ref([]);
const loading = ref(true);
const error = ref(null);
const posting = ref(false);

const activeCommentSection = ref(null);
const newComment = ref({});

const newPost = ref({
  content: '',
  image: null,
  imagePreview: null
});

const getAuthHeaders = () => {
  return {
    'Authorization': `Bearer ${localStorage.getItem('token')}`,
    'Accept': 'application/json'
  };
};

const fetchFeed = async () => {
  loading.value = true;
  try {
    const res = await fetch('http://localhost:8000/api/konnect/feed', { headers: getAuthHeaders() });
    if (!res.ok) throw new Error('Failed to load feed');
    posts.value = await res.json();
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

const handleImageChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    newPost.value.image = file;
    newPost.value.imagePreview = URL.createObjectURL(file);
  }
};

const removeImage = () => {
  newPost.value.image = null;
  newPost.value.imagePreview = null;
};

const submitPost = async () => {
  if (!newPost.value.content.trim() && !newPost.value.image) return;
  posting.value = true;
  
  const formData = new FormData();
  formData.append('content', newPost.value.content);
  if (newPost.value.image) {
    formData.append('image', newPost.value.image);
  }

  try {
    const res = await fetch('http://localhost:8000/api/konnect/posts', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      },
      body: formData
    });

    if (!res.ok) throw new Error('Failed to post');
    
    // Reset composer and fetch feed
    newPost.value.content = '';
    removeImage();
    await fetchFeed();
  } catch (err) {
    alert(err.message);
  } finally {
    posting.value = false;
  }
};

const deletePost = async (id) => {
  if (!confirm('Delete this post permanently?')) return;
  try {
    await fetch(`http://localhost:8000/api/konnect/posts/${id}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    });
    posts.value = posts.value.filter(p => p.id !== id);
  } catch (err) {
    console.error(err);
  }
};

const toggleLike = async (post) => {
  // Optimistic UI update
  post.is_liked_by_me = !post.is_liked_by_me;
  post.likes_count += post.is_liked_by_me ? 1 : -1;

  try {
    await fetch(`http://localhost:8000/api/konnect/posts/${post.id}/like`, {
      method: 'POST',
      headers: getAuthHeaders()
    });
  } catch (err) {
    // Revert on fail
    post.is_liked_by_me = !post.is_liked_by_me;
    post.likes_count += post.is_liked_by_me ? 1 : -1;
  }
};

const toggleComments = (postId) => {
  if (activeCommentSection.value === postId) {
    activeCommentSection.value = null;
  } else {
    activeCommentSection.value = postId;
  }
};

const submitComment = async (postId) => {
  const content = newComment.value[postId];
  if (!content?.trim()) return;

  try {
    const res = await fetch(`http://localhost:8000/api/konnect/posts/${postId}/comments`, {
      method: 'POST',
      headers: { ...getAuthHeaders(), 'Content-Type': 'application/json' },
      body: JSON.stringify({ content })
    });
    
    if (res.ok) {
      const comment = await res.json();
      const post = posts.value.find(p => p.id === postId);
      if (post) {
        post.comments.push(comment);
        post.comments_count++;
      }
      newComment.value[postId] = '';
    }
  } catch (err) {
    console.error(err);
  }
};

const deleteComment = async (postId, commentId) => {
  if (!confirm('Delete comment?')) return;
  try {
    await fetch(`http://localhost:8000/api/konnect/comments/${commentId}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    });
    const post = posts.value.find(p => p.id === postId);
    if (post) {
      post.comments = post.comments.filter(c => c.id !== commentId);
      post.comments_count--;
    }
  } catch (err) {
    console.error(err);
  }
};

const formatTime = (dateStr) => {
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMins / 60);
  const diffDays = Math.floor(diffHours / 24);

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const getImageUrl = (path) => `http://localhost:8000/api/file/${path}`;

onMounted(() => {
  fetchFeed();
});
</script>
